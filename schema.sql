CREATE TABLE IF NOT EXISTS `pacientes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(120) NOT NULL,
  `telefone` VARCHAR(40) NOT NULL,
  `email` VARCHAR(120) NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `medicos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(120) NOT NULL,
  `especialidade` VARCHAR(80) NOT NULL,
  `crm` VARCHAR(30) NOT NULL,
  `horario_atendimento` VARCHAR(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `consultas_medicas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `paciente_id` INT NOT NULL,
  `medico_id` INT NOT NULL,
  `data_consulta` DATE NOT NULL,
  `hora_consulta` TIME NOT NULL,
  `observacoes` TEXT NULL,
  `status` VARCHAR(30) DEFAULT 'Agendada',
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_consultas_paciente`
    FOREIGN KEY (`paciente_id`) REFERENCES `pacientes`(`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_consultas_medico`
    FOREIGN KEY (`medico_id`) REFERENCES `medicos`(`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX `idx_paciente_telefone` ON `pacientes` (`telefone`);
CREATE INDEX `idx_data_hora` ON `consultas_medicas` (`medico_id`, `data_consulta`, `hora_consulta`);
CREATE INDEX `idx_status` ON `consultas_medicas` (`status`);