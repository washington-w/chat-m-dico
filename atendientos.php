<?php
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
        die("Erro ao conectar ao banco de dados: " . $e->getMessage());
    }
}

if (isset($_GET['acao']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $acao = $_GET['acao'];

    if ($acao === 'concluir') {
        $stmt = $pdo->prepare("UPDATE consultas_medicas SET status = 'Concluída' WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: atendimentos.php?msg=" . urlencode("Consulta #{$id} marcada como Concluída!"));
        exit;
    } elseif ($acao === 'confirmar') {
        $stmt = $pdo->prepare("UPDATE consultas_medicas SET status = 'Confirmada' WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: atendimentos.php?msg=" . urlencode("Consulta #{$id} Confirmada!"));
        exit;
    } elseif ($acao === 'cancelar') {
        $stmt = $pdo->prepare("UPDATE consultas_medicas SET status = 'Cancelada' WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: atendimentos.php?msg=" . urlencode("Consulta #{$id} Cancelada."));
        exit;
    } elseif ($acao === 'excluir') {
        $stmt = $pdo->prepare("DELETE FROM consultas_medicas WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: atendimentos.php?msg=" . urlencode("Registro #{$id} excluído permanentemente."));
        exit;
    }
}

$filtroMedico = isset($_GET['medico_id']) ? intval($_GET['medico_id']) : 0;
$filtroData   = isset($_GET['data']) ? trim($_GET['data']) : '';
$filtroBusca  = isset($_GET['busca']) ? trim($_GET['busca']) : '';

$medicos = $pdo->query("SELECT * FROM medicos ORDER BY id ASC")->fetchAll();

$sql = "SELECT c.*, p.nome AS paciente_nome, p.telefone AS paciente_telefone, m.nome AS medico_nome, m.especialidade, m.crm 
        FROM consultas_medicas c 
        JOIN pacientes p ON c.paciente_id = p.id
        JOIN medicos m ON c.medico_id = m.id 
        WHERE 1=1";
$params = [];

if ($filtroMedico > 0) {
    $sql .= " AND c.medico_id = ?";
    $params[] = $filtroMedico;
}
if (!empty($filtroData)) {
    $sql .= " AND c.data_consulta = ?";
    $params[] = $filtroData;
}
if (!empty($filtroBusca)) {
    $sql .= " AND (p.nome LIKE ? OR p.telefone LIKE ? OR c.observacoes LIKE ?)";
    $like = "%{$filtroBusca}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY c.data_consulta ASC, c.hora_consulta ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$atendimentos = $stmt->fetchAll();

$totalGeral      = (int) $pdo->query("SELECT COUNT(*) FROM consultas_medicas")->fetchColumn();
$totalAgendadas  = (int) $pdo->query("SELECT COUNT(*) FROM consultas_medicas WHERE status IN ('Agendada', 'Confirmada')")->fetchColumn();
$totalConcluidas = (int) $pdo->query("SELECT COUNT(*) FROM consultas_medicas WHERE status = 'Concluída'")->fetchColumn();
$totalCanceladas = (int) $pdo->query("SELECT COUNT(*) FROM consultas_medicas WHERE status = 'Cancelada'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atendimentos Marcados - Clínica Saúde Integral</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #0f172a;
            color: #f8fafc;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
        }
        .navbar-custom {
            background-color: #1e293b;
            border-bottom: 1px solid #334155;
        }
        .card-stat {
            background-color: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            transition: transform 0.15s;
        }
        .card-stat:hover {
            transform: translateY(-2px);
        }
        .card-panel {
            background-color: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
        }
        .form-control-dark, .form-select-dark {
            background-color: #0f172a;
            border: 1px solid #334155;
            color: #f8fafc;
        }
        .form-control-dark:focus, .form-select-dark:focus {
            background-color: #0f172a;
            border-color: #0ea5e9;
            color: #f8fafc;
            box-shadow: 0 0 0 0.2rem rgba(14, 165, 233, 0.25);
        }
        .table-dark-custom {
            --bs-table-bg: #1e293b;
            --bs-table-hover-bg: #263349;
            --bs-table-color: #f8fafc;
            border-color: #334155;
            margin-bottom: 0;
        }
        .table-dark-custom th {
            background-color: #0f172a;
            color: #94a3b8;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #334155;
            padding: 12px 16px;
        }
        .table-dark-custom td {
            padding: 14px 16px;
            vertical-align: middle;
            border-bottom: 1px solid #334155;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="atendimentos.php">
                <i class="bi bi-calendar2-heart-fill text-info fs-4"></i>
                <span>Atendimentos Marcados</span>
            </a>
            <div class="d-flex align-items-center gap-2">
                <a href="index.php" class="btn btn-info btn-sm text-dark fw-bold">
                    <i class="bi bi-chat-dots-fill me-1"></i> Ir para o Chatbot IA
                </a>
                <a href="../../../home.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-folder2-open me-1"></i> Arquivos
                </a>
            </div>
        </div>
    </nav>

    <div class="container pb-5">

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm py-2" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($_GET['msg']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card card-stat p-3">
                    <div class="text-secondary small fw-semibold">Total de Consultas</div>
                    <div class="fs-3 fw-bold text-white mt-1"><?php echo $totalGeral; ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card card-stat p-3 border-info border-opacity-50">
                    <div class="text-info small fw-semibold">Agendadas / Ativas</div>
                    <div class="fs-3 fw-bold text-info mt-1"><?php echo $totalAgendadas; ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card card-stat p-3 border-success border-opacity-50">
                    <div class="text-success small fw-semibold">Concluídas</div>
                    <div class="fs-3 fw-bold text-success mt-1"><?php echo $totalConcluidas; ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card card-stat p-3 border-danger border-opacity-50">
                    <div class="text-danger small fw-semibold">Canceladas</div>
                    <div class="fs-3 fw-bold text-danger mt-1"><?php echo $totalCanceladas; ?></div>
                </div>
            </div>
        </div>

        <div class="card card-panel p-3 mb-4 shadow-sm">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small text-secondary mb-1">Buscar Paciente ou Motivo</label>
                    <input type="text" name="busca" class="form-control form-control-sm form-control-dark" placeholder="Nome, telefone ou observação..." value="<?php echo htmlspecialchars($filtroBusca); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-secondary mb-1">Filtrar por Médico</label>
                    <select name="medico_id" class="form-select form-select-sm form-select-dark">
                        <option value="0">Todos os Especialistas</option>
                        <?php foreach ($medicos as $m): ?>
                            <option value="<?php echo $m['id']; ?>" <?php echo ($filtroMedico === intval($m['id'])) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($m['especialidade'] . ' - ' . $m['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-secondary mb-1">Data da Consulta</label>
                    <input type="date" name="data" class="form-control form-control-sm form-control-dark" value="<?php echo htmlspecialchars($filtroData); ?>">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill fw-semibold">
                        <i class="bi bi-funnel"></i> Filtrar
                    </button>
                    <a href="atendimentos.php" class="btn btn-outline-secondary btn-sm" title="Limpar Filtros">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </form>
        </div>

        <div class="card card-panel shadow-sm overflow-hidden">
            <div class="card-header bg-dark border-bottom border-secondary border-opacity-50 d-flex justify-content-between align-items-center py-3 px-4">
                <span class="fw-semibold text-white">
                    <i class="bi bi-list-ul text-info me-2"></i> Lista de Atendimentos Agendados (<?php echo count($atendimentos); ?>)
                </span>
                <small class="text-secondary font-monospace">Clínica Saúde Integral</small>
            </div>

            <div class="table-responsive">
                <table class="table table-dark-custom table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Paciente</th>
                            <th>Médico / Especialidade</th>
                            <th>Data & Horário</th>
                            <th>Observações</th>
                            <th>Status</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($atendimentos)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-secondary py-5">
                                    <i class="bi bi-calendar-x fs-1 d-block mb-2 opacity-50"></i>
                                    Nenhum atendimento encontrado com os critérios selecionados.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($atendimentos as $at): 
                                $badgeClass = 'bg-info-subtle text-info border-info-subtle';
                                if ($at['status'] === 'Confirmada') $badgeClass = 'bg-primary-subtle text-primary border-primary-subtle';
                                if ($at['status'] === 'Concluída') $badgeClass = 'bg-success-subtle text-success border-success-subtle';
                                if ($at['status'] === 'Cancelada') $badgeClass = 'bg-danger-subtle text-danger border-danger-subtle';
                            ?>
                            <tr>
                                <td class="text-secondary font-monospace">#<?php echo $at['id']; ?></td>
                                <td>
                                    <div class="fw-bold text-white"><?php echo htmlspecialchars($at['paciente_nome']); ?></div>
                                    <small class="text-secondary"><i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($at['paciente_telefone']); ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-dark border border-secondary text-info mb-1"><?php echo htmlspecialchars($at['especialidade']); ?></span>
                                    <div class="small text-light"><?php echo htmlspecialchars($at['medico_nome']); ?></div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-white">
                                        <i class="bi bi-calendar-event text-warning me-1"></i>
                                        <?php echo date('d/m/Y', strtotime($at['data_consulta'])); ?>
                                    </div>
                                    <div class="small text-info fw-bold">
                                        <i class="bi bi-clock me-1"></i> <?php echo substr($at['hora_consulta'], 0, 5); ?>
                                    </div>
                                </td>
                                <td>
                                    <small class="text-secondary">
                                        <?php echo !empty($at['observacoes']) ? htmlspecialchars($at['observacoes']) : '<em>Sem observações</em>'; ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge border <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($at['status']); ?></span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="atendimentos.php?acao=confirmar&id=<?php echo $at['id']; ?>" class="btn btn-outline-primary" title="Confirmar Presença">
                                            <i class="bi bi-check2"></i>
                                        </a>
                                        <a href="atendimentos.php?acao=concluir&id=<?php echo $at['id']; ?>" class="btn btn-outline-success" title="Marcar como Concluída">
                                            <i class="bi bi-check-all"></i>
                                        </a>
                                        <a href="atendimentos.php?acao=cancelar&id=<?php echo $at['id']; ?>" class="btn btn-outline-warning" title="Cancelar Consulta">
                                            <i class="bi bi-slash-circle"></i>
                                        </a>
                                        <a href="atendimentos.php?acao=excluir&id=<?php echo $at['id']; ?>" class="btn btn-outline-danger" title="Excluir Registro" onclick="return confirm('Deseja excluir permanentemente este registro?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>