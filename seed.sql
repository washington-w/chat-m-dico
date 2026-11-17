INSERT INTO `pacientes` (`id`, `nome`, `telefone`, `email`) VALUES
(1, 'Ana Paula Ferreira', '(41) 99876-5432', 'ana.ferreira@email.com'),
(2, 'Lucas Mendes', '(41) 99123-4567', 'lucas.mendes@email.com'),
(3, 'Beatriz Lima (Resp: Carla)', '(41) 98888-1122', 'carla.lima@email.com')
ON DUPLICATE KEY UPDATE
  `nome` = VALUES(`nome`),
  `telefone` = VALUES(`telefone`),
  `email` = VALUES(`email`);

INSERT INTO `medicos` (`id`, `nome`, `especialidade`, `crm`, `horario_atendimento`) VALUES
(1, 'Dr. Carlos Eduardo Silva', 'Cardiologia', 'CRM/PR 12345', 'Segunda a Sexta, das 08:00 às 17:00'),
(2, 'Dra. Mariana Oliveira Santos', 'Dermatologia', 'CRM/PR 54321', 'Segunda a Quinta, das 09:00 às 18:00'),
(3, 'Dr. Rafael Henrique Costa', 'Pediatria', 'CRM/PR 98765', 'Terça a Sábado, das 08:00 às 16:00')
ON DUPLICATE KEY UPDATE
  `nome` = VALUES(`nome`),
  `especialidade` = VALUES(`especialidade`),
  `crm` = VALUES(`crm`),
  `horario_atendimento` = VALUES(`horario_atendimento`);

INSERT INTO `consultas_medicas` (`paciente_id`, `medico_id`, `data_consulta`, `hora_consulta`, `observacoes`, `status`) VALUES
(1, 1, '2026-11-18', '09:00:00', 'Check-up cardiológico de rotina', 'Confirmada'),
(2, 2, '2026-11-18', '14:30:00', 'Avaliação de manchas na pele', 'Agendada'),
(3, 3, '2026-11-19', '10:00:00', 'Consulta pediátrica de acompanhamento', 'Agendada');