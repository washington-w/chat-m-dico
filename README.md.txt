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
```

## Requisitos do Ambiente

- PHP 8.0 ou superior (com extensões `pdo_mysql` e `curl` habilitadas)
- MySQL 5.7+ ou MariaDB 10.3+
- Chave de API válida da DeepSeek

## Instalação e Configuração

1. **Banco de Dados:**
   - Aceda ao **phpMyAdmin** ou **Adminer** e selecione a base de dados `uilian77_aluno7`.
   - Importe ou execute o conteúdo do ficheiro `schema.sql` para criar as tabelas `medicos` e `consultas_medicas`.
   - Em seguida, execute o ficheiro `seed.sql` para cadastrar os 3 médicos especialistas e os registros de exemplo.

2. **Configuração da Conexão e API:**
   - Insira sua chave da API DeepSeek e a senha do banco de dados nos arquivos `index.php` e `atendimentos.php` (ou no arquivo `../conexao.php`).

3. **Execução:**
   - Envie os ficheiros para a pasta `chatmedico/` no servidor web.
   - Aceda via navegador a `http://seu-dominio/chatmedico/index.php` para utilizar o chatbot e `http://seu-dominio/chatmedico/atendimentos.php` para gerir a agenda.