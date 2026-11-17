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