# Clínica Saúde Integral — Sistema de Agendamento com IA

Sistema web desenvolvido em **PHP**, **MySQL** e **Bootstrap 5** integrado à API da **DeepSeek** para realizar o agendamento inteligente de consultas médicas por meio de uma recepcionista virtual (**Clara**), acompanhado de um painel administrativo para gestão dos atendimentos.

## Funcionalidades

- **Recepcionista Virtual (Clara):** Atendimento automatizado via chat com regras restritas ao escopo da clínica (Cardiologia, Dermatologia e Pediatria).
- **Prevenção de Conflitos:** Validação automática de datas e horários já ocupados no banco de dados antes de concluir a marcação.
- **Painel Administrativo (`atendimentos.php`):** Visualização de estatísticas em tempo real, filtros por paciente/médico/data e ações rápidas (Confirmar, Concluir, Cancelar e Excluir).
- **Atualização Dinâmica:** A barra lateral do chat reflete as consultas agendadas em tempo real sem necessidade de recarregar a página.

## Estrutura de Arquivos

```text
chatmedico/
├── index.php          # Interface do Chatbot IA e endpoints AJAX
├── atendimentos.php   # Painel administrativo de gestão de consultas
├── schema.sql         # Script DDL para criação das tabelas e índices
├── seed.sql           # Script DML com os médicos e agendamentos iniciais
└── README.md          # Documentação do sistema

Diário do Copiloto

Semana 1: Levantamento, Arquitetura e Modelagem do Banco de Dados
Dia 1 — Definição do Escopo e Requisitos: Levantamento das necessidades da Clínica Saúde Integral, definindo o fluxo principal de atendimento automatizado para três especialidades (Cardiologia, Dermatologia e Pediatria) e a necessidade de um painel administrativo para gestão de agenda.

Dia 2 — Escolha da Stack Tecnológica: Definição das ferramentas do projeto: PHP 8 para o backend e endpoints AJAX, MySQL para persistência relacional, Bootstrap 5 para interface responsiva e API da DeepSeek para o processamento de linguagem natural da recepcionista virtual (Clara).

Dia 3 — Configuração do Ambiente Cloud: Acesso e preparação da estrutura de diretórios no servidor uilian.cloud/alunos, criando a pasta chatmedico e validando as permissões de escrita e conexão via PDO no banco uilian77_aluno7.

Dia 4 — Modelagem Inicial do Banco de Dados: Criação da primeira versão do modelo relacional com as tabelas medicos e consultas_medicas, estabelecendo chaves primárias e o relacionamento básico entre especialistas e agendamentos.

Dia 5 — Normalização para Três Tabelas (schema.sql): Refatoração do banco para a Terceira Forma Normal (3FN), separando os dados cadastrais dos pacientes na tabela pacientes e vinculando consultas_medicas por meio das chaves estrangeiras paciente_id e medico_id com integridade referencial (ON DELETE CASCADE).

Dia 6 — Criação de Índices e Carga Inicial (seed.sql): Otimização das consultas de disponibilidade com índices compostos (idx_data_hora, idx_status e idx_paciente_telefone) e inserção dos registros iniciais dos três médicos especialistas e pacientes de teste.

Dia 7 — Validação do Esquema no Adminer: Importação dos scripts schema.sql e seed.sql no gerenciador web (phpmyadmin.php), resolvendo conflitos de tabelas antigas e confirmando a integridade das relações entre as três entidades.

Semana 2: Engenharia de Prompt e Desenvolvimento do Chatbot (index.php)
Dia 8 — Estruturação da Interface do Chat: Desenvolvimento do layout escuro (dark mode) em index.php com barra lateral fixa para exibição dos médicos cadastrados e painel principal de mensagens em tempo real.

Dia 9 — Integração Backend com a API DeepSeek: Implementação da comunicação via cURL em PHP enviando o histórico recente da sessão ($_SESSION['med_chat_history']) para o modelo deepseek-chat com temperatura baixa (0.3) para respostas previsíveis.

Dia 10 — Engenharia de Prompt e Guardrails da Clara: Criação das regras de comportamento da assistente virtual, restringindo o atendimento exclusivamente a agendamentos da clínica e bloqueando perguntas fora de escopo (programação, diagnósticos médicos ou assuntos gerais).

Dia 11 — Protocolo de Extração Estruturada ([AGENDAR_CONSULTA]): Implementação da tag JSON emitida pela IA apenas após a coleta completa dos cinco dados obrigatórios (especialidade/médico, nome, telefone, data e horário).

Dia 12 — Automação de Cadastro de Pacientes e Consultas: Desenvolvimento do parser em PHP via expressão regular (preg_match) para capturar o JSON da IA, verificar se o paciente já existe na tabela pacientes (inserindo-o caso seja novo) e gravar a consulta em consultas_medicas.

Dia 13 — Prevenção de Conflitos de Agenda: Implementação da verificação dupla de disponibilidade: injeção dos horários já ocupados no contexto da IA e consulta SQL de bloqueio (SELECT COUNT(*)) imediatamente antes do INSERT para evitar agendamentos duplicados no mesmo horário.

Dia 14 — Atualização Dinâmica da Barra Lateral (AJAX): Criação da rota index.php?action=send retornando JSON e atualização automática da lista de consultas agendadas no frontend sem recarregar a página.

Semana 3: Painel Administrativo e Fluxos de Gestão (atendimentos.php)
Dia 15 — Estruturação do Painel Administrativo: Criação do arquivo atendimentos.php conectado ao banco relacional com JOIN entre consultas_medicas, pacientes e medicos.

Dia 16 — Indicadores Gerenciais em Tempo Real: Desenvolvimento dos cartões superiores de métricas contabilizando o total geral de consultas, agendamentos ativos, atendimentos concluídos e cancelados.

Dia 17 — Sistema de Filtros Dinâmicos: Implementação da barra de busca combinada por nome/telefone do paciente, filtro por médico especialista e seleção por data específica utilizando consultas parametrizadas.

Dia 18 — Ações de Ciclo de Vida da Consulta: Criação dos endpoints de atualização de status (Confirmar, Concluir, Cancelar e Excluir) com feedback visual via alertas Bootstrap e redirecionamento limpo.

Dia 19 — Responsividade Mobile e Ajustes de UX: Adaptação da barra lateral do chat para dispositivos móveis com menu retrátil e formatação das respostas da IA com marked.js.

Dia 20 — Correção de Rotas e Navegação: Diagnóstico e resolução de erro 404 no botão de retorno ao gerenciador de arquivos do ambiente de alunos, ajustando o caminho relativo para ../../../home.php.

Dia 21 — Testes Integrados Ponta a Ponta: Simulação completa de agendamentos reais para as três especialidades, validando a gravação simultânea nas tabelas pacientes e consultas_medicas e a refletividade imediata no painel administrativo.

Semana 4: Auditoria de Segurança, Documentação e Publicação
Dia 22 — Blindagem contra SQL Injection: Revisão de todas as consultas dinâmicas em index.php e atendimentos.php, garantindo o uso exclusivo de Prepared Statements com PDO.

Dia 23 — Mitigação de Cross-Site Scripting (XSS): Inclusão da biblioteca DOMPurify no frontend para sanitizar o HTML gerado pelo parser Markdown antes da injeção no DOM, complementando o htmlspecialchars() no backend.

Dia 24 — Validação Rigorosa de Datas e Integridade: Implementação de checagens no servidor usando DateTime::createFromFormat() para bloquear datas inválidas, horários malformados, IDs de médicos inexistentes e agendamentos retroativos (anteriores à data atual).

Dia 25 — Proteção de Cancelamento e Segurança SSL: Ativação da verificação rigorosa de certificados HTTPS no cURL (CURLOPT_SSL_VERIFYPEER e CURLOPT_SSL_VERIFYHOST) e adição de PIN administrativo (ADMIN_PIN com comparação segura via hash_equals) para cancelar consultas na barra lateral.

Dia 26 — Depuração e Tratamento de Exceções: Resolução de falhas HTTP 500 causadas por divergências de esquema durante a migração para três tabelas, validando a limpeza e recriação das estruturas no MySQL.

Dia 27 — Sanitização de Credenciais para Repositório Público: Substituição da chave da API DeepSeek, senhas do banco de dados e PIN administrativo por placeholders seguros nos arquivos de código-fonte.

Dia 28 — Redação da Documentação Técnica (README.md): Elaboração do guia completo do projeto contendo arquitetura de arquivos, requisitos de ambiente, instruções de importação SQL e configuração.

Dia 29 — Revisão Final da Estrutura de Arquivos: Conferência dos cinco artefatos finais (index.php, atendimentos.php, schema.sql, seed.sql e README.md) garantindo ausência de credenciais expostas e funcionamento pleno no servidor.

Dia 30 — Publicação no GitHub e Entrega: Criação do repositório público no GitHub, upload dos arquivos finais sanitizados e validação final do sistema em produção.
