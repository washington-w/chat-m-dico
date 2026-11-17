<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DEEPSEEK_API_KEY', 'SUA_CHAVE_API_DEEPSEEK_AQUI');
define('DEEPSEEK_API_URL', 'https://api.deepseek.com/chat/completions');
define('ADMIN_PIN', 'SEU_PIN_ADMIN_AQUI');

$pdo = null;
$dbStatus = false;

if (file_exists(__DIR__ . '/../conexao.php')) {
    try {
        require_once __DIR__ . '/../conexao.php';
        if (isset($pdo) && $pdo instanceof PDO) {
            $dbStatus = true;
        }
    } catch (Exception $e) {
        $dbStatus = false;
    }
}

if (!$dbStatus) {
    try {
        $pdo = new PDO(
            "mysql:host=SEU_HOST_MYSQL;dbname=SEU_BANCO_MYSQL;charset=utf8mb4",
            "SEU_USUARIO_MYSQL",
            "SUA_SENHA_MYSQL_AQUI",
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        $dbStatus = true;
    } catch (Exception $e) {
        $dbStatus = false;
    }
}

if ($dbStatus) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS medicos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(120) NOT NULL,
        especialidade VARCHAR(80) NOT NULL,
        crm VARCHAR(30) NOT NULL,
        horario_atendimento VARCHAR(120) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS consultas_medicas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        paciente_nome VARCHAR(120) NOT NULL,
        paciente_telefone VARCHAR(40) NOT NULL,
        medico_id INT NOT NULL,
        data_consulta DATE NOT NULL,
        hora_consulta TIME NOT NULL,
        observacoes TEXT NULL,
        status VARCHAR(30) DEFAULT 'Agendada',
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (medico_id) REFERENCES medicos(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $countMedicos = (int) $pdo->query("SELECT COUNT(*) FROM medicos")->fetchColumn();
    if ($countMedicos === 0) {
        $stmtMed = $pdo->prepare("INSERT INTO medicos (nome, especialidade, crm, horario_atendimento) VALUES (?, ?, ?, ?)");
        $stmtMed->execute(['Dr. Carlos Eduardo Silva', 'Cardiologia', 'CRM/PR 12345', 'Segunda a Sexta, das 08:00 às 17:00']);
        $stmtMed->execute(['Dra. Mariana Oliveira Santos', 'Dermatologia', 'CRM/PR 54321', 'Segunda a Quinta, das 09:00 às 18:00']);
        $stmtMed->execute(['Dr. Rafael Henrique Costa', 'Pediatria', 'CRM/PR 98765', 'Terça a Sábado, das 08:00 às 16:00']);
    }
}

$medicosLista = $dbStatus ? $pdo->query("SELECT * FROM medicos ORDER BY id ASC")->fetchAll() : [];
$consultasLista = $dbStatus ? $pdo->query("
    SELECT c.*, m.nome AS medico_nome, m.especialidade 
    FROM consultas_medicas c 
    JOIN medicos m ON c.medico_id = m.id 
    WHERE c.status != 'Cancelada'
    ORDER BY c.data_consulta ASC, c.hora_consulta ASC
")->fetchAll() : [];

if (!isset($_SESSION['med_chat_history'])) {
    $_SESSION['med_chat_history'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    header('Content-Type: application/json; charset=utf-8');

    if ($_GET['action'] === 'clear') {
        $_SESSION['med_chat_history'] = [];
        echo json_encode(['success' => true]);
        exit;
    }

    if ($_GET['action'] === 'cancel_appointment') {
        $input = json_decode(file_get_contents('php://input'), true);
        $idConsulta = intval($input['id'] ?? 0);
        $pinInformado = trim((string) ($input['pin'] ?? ''));

        if (!hash_equals(ADMIN_PIN, $pinInformado)) {
            echo json_encode(['success' => false, 'error' => 'PIN administrativo incorreto.']);
            exit;
        }

        if ($dbStatus && $idConsulta > 0) {
            $stmtDel = $pdo->prepare("DELETE FROM consultas_medicas WHERE id = ?");
            $stmtDel->execute([$idConsulta]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Não foi possível cancelar a consulta.']);
        }
        exit;
    }

    if ($_GET['action'] === 'send') {
        $input = json_decode(file_get_contents('php://input'), true);
        $userMsg = trim((string) ($input['message'] ?? ''));

        if (empty($userMsg)) {
            echo json_encode(['success' => false, 'error' => 'Mensagem vazia.']);
            exit;
        }

        $_SESSION['med_chat_history'][] = ['role' => 'user', 'content' => $userMsg];

        $textoMedicos = "";
        foreach ($medicosLista as $m) {
            $textoMedicos .= "- ID {$m['id']}: {$m['nome']} | Especialidade: {$m['especialidade']} | {$m['crm']} | Horário: {$m['horario_atendimento']}\n";
        }

        $textoOcupados = "";
        if (!empty($consultasLista)) {
            foreach ($consultasLista as $c) {
                $textoOcupados .= "- Médico ID {$c['medico_id']} ({$c['medico_nome']}) ocupado em {$c['data_consulta']} às {$c['hora_consulta']}\n";
            }
        } else {
            $textoOcupados = "Nenhum horário ocupado no momento (agenda livre).";
        }

        $dataHoje = date('Y-m-d');
        $diaSemana = date('l');

        $systemPrompt = <<<PROMPT
Você é a Clara, a assistente virtual EXCLUSIVA de agendamento de consultas médicas da Clínica Saúde Integral.
Data atual do sistema: {$dataHoje} ({$diaSemana}).

=== REGRAS ABSOLUTAS E INQUEBRÁVEIS ===
1. ESCOPO EXCLUSIVO: Você SÓ pode atender solicitações relacionadas a:
   - Informações sobre os 3 médicos e especialidades da clínica.
   - Agendamento de consultas médicas.
   - Verificação de horários disponíveis.
2. RECUSA OBRIGATÓRIA DE OUTROS ASSUNTOS: Você NÃO PODE responder sobre NENHUM outro assunto que não seja consultas médicas nesta clínica (ex: programação, PHP, matemática, história, receitas, esportes, piadas, conselhos amorosos, política, diagnósticos médicos complexos ou receitas de remédios).
   - Se o usuário perguntar qualquer coisa fora do escopo de agendamento de consultas médicas, recuse educadamente dizendo:
     "Desculpe, sou uma assistente virtual exclusiva para agendamento de consultas médicas na Clínica Saúde Integral e não posso atender outros assuntos. Gostaria de agendar uma consulta com um de nossos especialistas?"

=== NOSSOS 3 MÉDICOS E ESPECIALIDADES ===
{$textoMedicos}

=== HORÁRIOS JÁ OCUPADOS NA AGENDA ===
{$textoOcupados}

=== FLUXO DE AGENDAMENTO ===
Para concluir o agendamento de uma consulta, você precisa coletar do paciente:
1. Qual médico ou especialidade deseja (Cardiologia, Dermatologia ou Pediatria).
2. Nome completo do paciente.
3. Telefone/WhatsApp de contato.
4. Data desejada (no formato YYYY-MM-DD, verificando se não cai em horário ocupado, se não é uma data passada e se está dentro do dia da semana do médico).
5. Horário desejado (HH:MM, ex: 09:00, 14:30).

IMPORTANTE: Assim que o paciente fornecer TODOS esses dados e você confirmar que o horário está livre, emita EXATAMENTE no final da sua resposta a seguinte tag JSON (para que nosso sistema PHP grave a consulta no banco de dados MySQL):
[AGENDAR_CONSULTA]{"medico_id": 1, "paciente_nome": "Nome Aqui", "paciente_telefone": "44999999999", "data_consulta": "2026-11-18", "hora_consulta": "14:00", "observacoes": "Motivo resumido"}[/AGENDAR_CONSULTA]

Emita a tag [AGENDAR_CONSULTA] APENAS quando tiver todos os 5 dados confirmados. Seja educada, objetiva e empática.
PROMPT;

        $messagesPayload = [
            ['role' => 'system', 'content' => $systemPrompt]
        ];

        $recentes = array_slice($_SESSION['med_chat_history'], -12);
        foreach ($recentes as $msgItem) {
            $messagesPayload[] = [
                'role' => $msgItem['role'],
                'content' => $msgItem['content']
            ];
        }

        $postData = [
            'model' => 'deepseek-chat',
            'messages' => $messagesPayload,
            'temperature' => 0.3,
            'stream' => false
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, DEEPSEEK_API_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_TIMEOUT, 45);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . DEEPSEEK_API_KEY
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || empty($response)) {
            echo json_encode(['success' => false, 'error' => "Falha ao comunicar com a DeepSeek (HTTP {$httpCode})."]);
            exit;
        }

        $data = json_decode($response, true);
        $botReply = $data['choices'][0]['message']['content'] ?? 'Desculpe, não consegui processar sua solicitação.';

        $consultaAgendada = null;
        if (preg_match('/\[AGENDAR_CONSULTA\](.*?)\[\/AGENDAR_CONSULTA\]/s', $botReply, $matches)) {
            $jsonAgendamento = json_decode(trim($matches[1]), true);
            $botReply = trim(str_replace($matches[0], '', $botReply));

            if ($dbStatus && is_array($jsonAgendamento) && !empty($jsonAgendamento['medico_id']) && !empty($jsonAgendamento['paciente_nome'])) {
                $medId   = intval($jsonAgendamento['medico_id']);
                $pacNome = mb_substr(trim((string) $jsonAgendamento['paciente_nome']), 0, 120);
                $pacTel  = mb_substr(trim((string) ($jsonAgendamento['paciente_telefone'] ?? 'Não informado')), 0, 40);
                $dtCons  = trim((string) ($jsonAgendamento['data_consulta'] ?? ''));
                $hrCons  = trim((string) ($jsonAgendamento['hora_consulta'] ?? ''));
                if (strlen($hrCons) === 5) $hrCons .= ':00';
                $obs     = trim((string) ($jsonAgendamento['observacoes'] ?? ''));

                $dataValida = DateTime::createFromFormat('Y-m-d', $dtCons);
                $horaValida = DateTime::createFromFormat('H:i:s', $hrCons);

                $stmtMedExists = $pdo->prepare("SELECT COUNT(*) FROM medicos WHERE id = ?");
                $stmtMedExists->execute([$medId]);
                $medicoExiste = (int) $stmtMedExists->fetchColumn() > 0;

                if (!$medicoExiste || !$dataValida || $dataValida->format('Y-m-d') !== $dtCons || !$horaValida || $horaValida->format('H:i:s') !== $hrCons) {
                    $botReply .= "\n\n⚠️ *Aviso do Sistema:* Os dados de data, horário ou médico informados são inválidos. Por favor, verifique e tente novamente.";
                } elseif ($dtCons < date('Y-m-d')) {
                    $botReply .= "\n\n⚠️ *Aviso do Sistema:* Não é possível agendar consultas para datas anteriores ao dia de hoje.";
                } else {
                    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM consultas_medicas WHERE medico_id = ? AND data_consulta = ? AND hora_consulta = ? AND status != 'Cancelada'");
                    $stmtCheck->execute([$medId, $dtCons, $hrCons]);
                    if ($stmtCheck->fetchColumn() > 0) {
                        $botReply .= "\n\n⚠️ *Aviso do Sistema:* Este horário já acabou de ser ocupado para este médico. Por favor, escolha outro horário.";
                    } else {
                        $stmtIns = $pdo->prepare("INSERT INTO consultas_medicas (paciente_nome, paciente_telefone, medico_id, data_consulta, hora_consulta, observacoes) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmtIns->execute([$pacNome, $pacTel, $medId, $dtCons, $hrCons, $obs]);
                        $consultaAgendada = [
                            'id'       => $pdo->lastInsertId(),
                            'paciente' => $pacNome,
                            'data'     => date('d/m/Y', strtotime($dtCons)),
                            'hora'     => substr($hrCons, 0, 5)
                        ];
                    }
                }
            }
        }

        $_SESSION['med_chat_history'][] = ['role' => 'assistant', 'content' => $botReply];

        $consultasAtualizadas = $dbStatus ? $pdo->query("
            SELECT c.*, m.nome AS medico_nome, m.especialidade 
            FROM consultas_medicas c 
            JOIN medicos m ON c.medico_id = m.id 
            WHERE c.status != 'Cancelada'
            ORDER BY c.data_consulta ASC, c.hora_consulta ASC
        ")->fetchAll() : [];

        echo json_encode([
            'success'   => true,
            'reply'     => $botReply,
            'agendada'  => $consultaAgendada,
            'consultas' => $consultasAtualizadas
        ]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clínica Saúde Integral - Agendamento com IA</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/dompurify@3.1.6/dist/purify.min.js"></script>

    <style>
        :root {
            --bg-main: #0f172a;
            --bg-sidebar: #1e293b;
            --accent: #0ea5e9;
            --accent-hover: #0284c7;
            --border-ui: #334155;
            --user-bubble: #0284c7;
            --bot-bubble: #1e293b;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 0;
            height: 100vh;
            background-color: var(--bg-main);
            color: #f8fafc;
            font-family: 'Inter', sans-serif;
            display: flex;
            overflow: hidden;
        }

        .sidebar {
            width: 340px;
            background-color: var(--bg-sidebar);
            border-right: 1px solid var(--border-ui);
            display: flex;
            flex-direction: column;
            padding: 18px;
            overflow-y: auto;
            flex-shrink: 0;
        }
        @media (max-width: 992px) {
            .sidebar {
                position: fixed;
                left: -340px;
                top: 0;
                height: 100vh;
                z-index: 9999;
                transition: left 0.3s ease;
            }
            .sidebar.open { left: 0; }
        }

        .clinic-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border-ui);
            margin-bottom: 16px;
        }
        .clinic-logo {
            width: 42px;
            height: 42px;
            background: rgba(14, 165, 233, 0.15);
            border: 1px solid var(--accent);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent);
            font-size: 1.3rem;
        }

        .doctor-card {
            background-color: #0f172a;
            border: 1px solid var(--border-ui);
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 10px;
        }

        .appt-card {
            background-color: #0f172a;
            border-left: 3px solid var(--accent);
            border-top: 1px solid var(--border-ui);
            border-right: 1px solid var(--border-ui);
            border-bottom: 1px solid var(--border-ui);
            border-radius: 8px;
            padding: 10px 12px;
            margin-bottom: 8px;
            font-size: 0.85rem;
        }

        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            height: 100vh;
            background-color: var(--bg-main);
        }

        .chat-header {
            background-color: var(--bg-sidebar);
            border-bottom: 1px solid var(--border-ui);
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .message-row {
            display: flex;
            max-width: 80%;
        }
        .message-row.user {
            align-self: flex-end;
        }
        .message-row.bot {
            align-self: flex-start;
        }

        .bubble {
            padding: 12px 16px;
            border-radius: 14px;
            font-size: 0.94rem;
            line-height: 1.5;
            word-wrap: break-word;
        }
        .bubble p:last-child { margin-bottom: 0; }
        .message-row.user .bubble {
            background-color: var(--user-bubble);
            color: #ffffff;
            border-bottom-right-radius: 4px;
        }
        .message-row.bot .bubble {
            background-color: var(--bot-bubble);
            border: 1px solid var(--border-ui);
            color: #f8fafc;
            border-bottom-left-radius: 4px;
        }

        .chat-input-area {
            background-color: var(--bg-sidebar);
            border-top: 1px solid var(--border-ui);
            padding: 16px 24px;
        }
        .chat-input {
            background-color: #0f172a;
            border: 1px solid var(--border-ui);
            color: #f8fafc;
        }
        .chat-input:focus {
            background-color: #0f172a;
            border-color: var(--accent);
            color: #f8fafc;
            box-shadow: 0 0 0 0.2rem rgba(14, 165, 233, 0.25);
        }
    </style>
</head>
<body>

    <aside class="sidebar" id="sidebar">
        <div class="clinic-brand">
            <div class="clinic-logo">
                <i class="bi bi-heart-pulse-fill"></i>
            </div>
            <div>
                <h6 class="mb-0 fw-bold text-white">Saúde Integral</h6>
                <small class="text-info">Agendamento Inteligente</small>
            </div>
        </div>

        <div class="mb-3">
            <div class="text-uppercase text-secondary fw-bold mb-2" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                Nossos Especialistas
            </div>
            <?php foreach ($medicosLista as $m): ?>
                <div class="doctor-card">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="badge bg-info-subtle text-info border border-info-subtle"><?php echo htmlspecialchars($m['especialidade']); ?></span>
                        <small class="text-secondary"><?php echo htmlspecialchars($m['crm']); ?></small>
                    </div>
                    <div class="fw-semibold text-white small"><?php echo htmlspecialchars($m['nome']); ?></div>
                    <div class="text-secondary" style="font-size: 0.75rem;">
                        <i class="bi bi-clock me-1"></i><?php echo htmlspecialchars($m['horario_atendimento']); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="flex-grow-1">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-uppercase text-secondary fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                    Consultas Agendadas
                </span>
                <span class="badge bg-secondary" id="badgeTotalConsultas"><?php echo count($consultasLista); ?></span>
            </div>

            <div id="listaConsultasSidebar">
                <?php if (empty($consultasLista)): ?>
                    <div class="text-center text-secondary small py-3 border border-secondary border-opacity-25 rounded">
                        Nenhuma consulta marcada.
                    </div>
                <?php else: ?>
                    <?php foreach ($consultasLista as $c): ?>
                        <div class="appt-card" id="appt-<?php echo (int) $c['id']; ?>">
                            <div class="d-flex justify-content-between align-items-start">
                                <strong class="text-white"><?php echo htmlspecialchars($c['paciente_nome']); ?></strong>
                                <button class="btn btn-link text-danger p-0 lh-1" title="Cancelar" onclick="cancelarConsulta(<?php echo (int) $c['id']; ?>)">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            </div>
                            <div class="text-info small"><?php echo htmlspecialchars($c['especialidade']); ?> - <?php echo htmlspecialchars($c['medico_nome']); ?></div>
                            <div class="text-secondary mt-1" style="font-size: 0.78rem;">
                                <i class="bi bi-calendar-event me-1"></i><?php echo date('d/m/Y', strtotime($c['data_consulta'])); ?>
                                às <strong><?php echo substr($c['hora_consulta'], 0, 5); ?></strong>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="pt-3 mt-3 border-top border-secondary border-opacity-50 d-grid gap-2">
            <a href="atendimentos.php" class="btn btn-outline-info btn-sm fw-semibold">
                <i class="bi bi-table me-1"></i> Painel Administrativo
            </a>
        </div>
    </aside>

    <main class="chat-main">
        <header class="chat-header">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-outline-secondary btn-sm d-lg-none" onclick="document.getElementById('sidebar').classList.toggle('open')">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <h6 class="mb-0 fw-bold text-white">Clara • Recepcionista Virtual</h6>
                    <small class="text-success"><i class="bi bi-circle-fill me-1" style="font-size: 0.55rem;"></i>Online • Banco: <?php echo $dbStatus ? 'Conectado' : 'Erro de Conexão'; ?></small>
                </div>
            </div>
            <button class="btn btn-outline-secondary btn-sm" onclick="limparChat()">
                <i class="bi bi-trash3 me-1"></i> Nova Conversa
            </button>
        </header>

        <div class="chat-messages" id="chatMessages">
            <?php if (empty($_SESSION['med_chat_history'])): ?>
                <div class="message-row bot">
                    <div class="bubble">
                        Olá! Sou a <strong>Clara</strong>, assistente virtual da <strong>Clínica Saúde Integral</strong>. 🩺<br><br>
                        Posso ajudar você a agendar consultas em <strong>Cardiologia</strong>, <strong>Dermatologia</strong> ou <strong>Pediatria</strong>. Qual especialidade você procura hoje?
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($_SESSION['med_chat_history'] as $msg): ?>
                    <div class="message-row <?php echo $msg['role'] === 'user' ? 'user' : 'bot'; ?>">
                        <div class="bubble"><?php echo nl2br(htmlspecialchars($msg['content'])); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="chat-input-area">
            <form id="chatForm" class="d-flex gap-2">
                <input type="text" id="userInput" class="form-control chat-input" placeholder="Digite sua mensagem para agendar uma consulta..." autocomplete="off" required>
                <button type="submit" id="btnSend" class="btn btn-info text-dark fw-bold px-4">
                    <i class="bi bi-send-fill"></i>
                </button>
            </form>
        </div>
    </main>

    <script>
        const chatMessages = document.getElementById('chatMessages');
        const chatForm = document.getElementById('chatForm');
        const userInput = document.getElementById('userInput');
        const btnSend = document.getElementById('btnSend');

        function scrollToBottom() {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        scrollToBottom();

        function renderMarkdownSeguro(text) {
            return DOMPurify.sanitize(marked.parse(text));
        }

        function appendMessage(role, text) {
            const row = document.createElement('div');
            row.className = `message-row ${role}`;
            const bubble = document.createElement('div');
            bubble.className = 'bubble';
            if (role === 'bot') {
                bubble.innerHTML = renderMarkdownSeguro(text);
            } else {
                bubble.textContent = text;
                bubble.innerHTML = bubble.innerHTML.replace(/\n/g, '<br>');
            }
            row.appendChild(bubble);
            chatMessages.appendChild(row);
            scrollToBottom();
            return bubble;
        }

        function atualizarSidebar(consultas) {
            const container = document.getElementById('listaConsultasSidebar');
            const badge = document.getElementById('badgeTotalConsultas');
            badge.textContent = consultas.length;
            container.innerHTML = '';

            if (consultas.length === 0) {
                const vazio = document.createElement('div');
                vazio.className = 'text-center text-secondary small py-3 border border-secondary border-opacity-25 rounded';
                vazio.textContent = 'Nenhuma consulta marcada.';
                container.appendChild(vazio);
                return;
            }

            consultas.forEach(c => {
                const partesData = c.data_consulta.split('-');
                const dataFormatada = `${partesData[2]}/${partesData[1]}/${partesData[0]}`;
                const horaFormatada = c.hora_consulta.substring(0, 5);
                const idSeguro = parseInt(c.id, 10);

                const card = document.createElement('div');
                card.className = 'appt-card';
                card.id = `appt-${idSeguro}`;

                const header = document.createElement('div');
                header.className = 'd-flex justify-content-between align-items-start';

                const nomeEl = document.createElement('strong');
                nomeEl.className = 'text-white';
                nomeEl.textContent = c.paciente_nome;

                const btnCancel = document.createElement('button');
                btnCancel.className = 'btn btn-link text-danger p-0 lh-1';
                btnCancel.title = 'Cancelar';
                btnCancel.innerHTML = '<i class="bi bi-x-circle"></i>';
                btnCancel.onclick = () => cancelarConsulta(idSeguro);

                header.appendChild(nomeEl);
                header.appendChild(btnCancel);

                const medEl = document.createElement('div');
                medEl.className = 'text-info small';
                medEl.textContent = `${c.especialidade} - ${c.medico_nome}`;

                const dataEl = document.createElement('div');
                dataEl.className = 'text-secondary mt-1';
                dataEl.style.fontSize = '0.78rem';
                dataEl.innerHTML = `<i class="bi bi-calendar-event me-1"></i>${DOMPurify.sanitize(dataFormatada)} às <strong>${DOMPurify.sanitize(horaFormatada)}</strong>`;

                card.appendChild(header);
                card.appendChild(medEl);
                card.appendChild(dataEl);
                container.appendChild(card);
            });
        }

        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const message = userInput.value.trim();
            if (!message) return;

            appendMessage('user', message);
            userInput.value = '';
            userInput.disabled = true;
            btnSend.disabled = true;

            const typingBubble = appendMessage('bot', '<em>Digitando...</em>');

            try {
                const res = await fetch('index.php?action=send', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message })
                });
                const data = await res.json();

                if (data.success) {
                    typingBubble.innerHTML = renderMarkdownSeguro(data.reply);
                    if (data.consultas) {
                        atualizarSidebar(data.consultas);
                    }
                } else {
                    typingBubble.textContent = `Erro: ${data.error || 'Falha na resposta.'}`;
                    typingBubble.classList.add('text-danger');
                }
            } catch (err) {
                typingBubble.textContent = 'Erro de conexão com o servidor.';
                typingBubble.classList.add('text-danger');
            } finally {
                userInput.disabled = false;
                btnSend.disabled = false;
                userInput.focus();
                scrollToBottom();
            }
        });

        async function cancelarConsulta(id) {
            const pin = prompt('Digite o PIN administrativo para cancelar esta consulta:');
            if (pin === null) return;

            const res = await fetch('index.php?action=cancel_appointment', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, pin })
            });
            const data = await res.json();
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Não foi possível cancelar.');
            }
        }

        async function limparChat() {
            await fetch('index.php?action=clear', { method: 'POST' });
            location.reload();
        }
    </script>
</body>
</html>
