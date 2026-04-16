-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 28/11/2025 às 03:48
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `medsync`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `administradores`
--

CREATE TABLE `administradores` (
  `admin_id` int(11) NOT NULL,
  `admin_nome` varchar(100) NOT NULL,
  `admin_email` varchar(100) NOT NULL,
  `admin_telefone` varchar(20) DEFAULT NULL,
  `admin_senha` varchar(255) NOT NULL,
  `token_recuperacao` varchar(255) DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `administradores`
--

INSERT INTO `administradores` (`admin_id`, `admin_nome`, `admin_email`, `admin_telefone`, `admin_senha`, `token_recuperacao`, `data_criacao`) VALUES
(4, 'adm principal', 'adm@gmai..com', '11999999999', '$2y$10$buCWbgVrwqwPWr4DpKjUSuKd7ITIHZ1QvZsGkAA41XSfuov.q.TpW', NULL, '2025-07-01 22:53:01');

-- --------------------------------------------------------

--
-- Estrutura para tabela `anamnese`
--

CREATE TABLE `anamnese` (
  `id` int(11) NOT NULL,
  `paciente_maior_id` int(11) DEFAULT NULL,
  `paciente_menor_id` int(11) DEFAULT NULL,
  `medico_id` int(11) NOT NULL,
  `data_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `peso` decimal(5,2) DEFAULT NULL,
  `altura` decimal(4,2) DEFAULT NULL,
  `pressao_arterial` varchar(20) DEFAULT NULL,
  `temperatura` decimal(4,2) DEFAULT NULL,
  `frequencia_cardiaca` int(11) DEFAULT NULL,
  `queixa_principal` text DEFAULT NULL,
  `antecedentes_pessoal` text DEFAULT NULL,
  `antecedentes_familiares` text DEFAULT NULL,
  `uso_medicamentos` text DEFAULT NULL,
  `alergias` text DEFAULT NULL,
  `observacoes_gerais` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `atestado`
--

CREATE TABLE `atestado` (
  `atestado_id` int(11) NOT NULL,
  `medico_id` int(11) NOT NULL,
  `paciente_maior_id` int(11) DEFAULT NULL,
  `paciente_menor_id` int(11) DEFAULT NULL,
  `responsavel_id` int(11) DEFAULT NULL,
  `data_emissao` date NOT NULL,
  `dias_afastamento` int(11) NOT NULL,
  `descricao` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `atestado`
--

INSERT INTO `atestado` (`atestado_id`, `medico_id`, `paciente_maior_id`, `paciente_menor_id`, `responsavel_id`, `data_emissao`, `dias_afastamento`, `descricao`) VALUES
(1, 3, 6, NULL, NULL, '2025-10-17', 7, 'Atesto, para os devidos fins, que a Sra. Maria Silva Santos necessita de uma semana de afastamento de suas atividades laborais, a partir desta data, por motivo de saúde.'),
(2, 1, 6, NULL, NULL, '2025-10-10', 3, 'Paciente diagnosticada com hipertensão arterial. Recomenda-se repouso e afastamento das atividades laborais por 3 dias para ajuste medicamentoso e controle da pressão arterial.'),
(3, 3, 5, NULL, NULL, '2025-09-15', 5, 'Gestante com quadro de náuseas e vômitos intensos. Necessário afastamento por 5 dias para repouso e adequada hidratação. Retorno para reavaliação.'),
(4, 4, NULL, 1, 1, '2025-10-20', 2, 'Criança com quadro viral e febre. Recomenda-se afastamento escolar por 2 dias para repouso e recuperação. Retorno às atividades quando estiver sem febre há 24 horas.'),
(5, 1, 6, NULL, NULL, '2025-10-10', 3, 'Paciente diagnosticada com hipertensão arterial. Recomenda-se repouso e afastamento das atividades laborais por 3 dias para ajuste medicamentoso e controle da pressão arterial.'),
(6, 3, 5, NULL, NULL, '2025-09-15', 5, 'Gestante com quadro de náuseas e vômitos intensos. Necessário afastamento por 5 dias para repouso e adequada hidratação. Retorno para reavaliação.'),
(7, 4, NULL, 1, 1, '2025-10-20', 2, 'Criança com quadro viral e febre. Recomenda-se afastamento escolar por 2 dias para repouso e recuperação. Retorno às atividades quando estiver sem febre há 24 horas.'),
(8, 1, 6, NULL, NULL, '2025-10-10', 2, 'Paciente em investigação de arritmia cardíaca. Necessário afastamento por 2 dias para realização de exames complementares e ajuste medicamentoso.'),
(9, 3, 5, NULL, NULL, '2025-09-15', 3, 'Gestante com quadro de hiperêmese gravídica. Recomenda-se afastamento por 3 dias para repouso, hidratação e controle dos vômitos.'),
(10, 4, 3, NULL, NULL, '2025-11-05', 1, 'Paciente com cefaleia tensional. Afastamento por 1 dia para repouso e controle da dor. Retorno às atividades com melhora do quadro.'),
(11, 1, 7, NULL, NULL, '2025-11-28', 2, 'Paciente com hipertensão arterial descompensada. Necessário afastamento por 2 dias para ajuste terapêutico e controle pressórico.'),
(12, 4, NULL, 1, 1, '2025-10-20', 2, 'Criança com quadro de cefaleia recorrente e febre. Afastamento escolar por 2 dias para repouso, hidratação e acompanhamento da evolução do quadro.'),
(13, 1, NULL, 1, 1, '2025-11-08', 1, 'Paciente pediátrico em investigação de anemia. Afastamento por 1 dia para realização de exames laboratoriais e adequada recuperação.'),
(14, 4, NULL, 2, 6, '2025-10-20', 3, 'Adolescente com crise de enxaqueca com aura. Afastamento escolar por 3 dias para controle da dor, fotofobia e fonofobia. Retorno gradual às atividades.'),
(15, 1, 10, NULL, NULL, '2025-11-28', 1, 'Paciente com labirintite e vertigem posicional. Afastamento por 1 dia para repouso e adaptação à nova medicação prescrita.');

-- --------------------------------------------------------

--
-- Estrutura para tabela `clinica`
--

CREATE TABLE `clinica` (
  `clinica_id` int(11) NOT NULL,
  `clinica_cnpj` varchar(14) NOT NULL,
  `clinica_nome_fant` varchar(100) NOT NULL,
  `clinica_razao_social` varchar(100) NOT NULL,
  `clinica_inscricao_estadual` varchar(20) DEFAULT NULL,
  `clinica_inscricao_municipal` varchar(20) DEFAULT NULL,
  `clinica_telefone` varchar(15) NOT NULL,
  `clinica_email` varchar(100) NOT NULL,
  `clinica_endereco` varchar(200) NOT NULL,
  `clinica_uf` char(2) NOT NULL,
  `clinica_especialidade` varchar(100) NOT NULL,
  `clinica_senha` varchar(255) NOT NULL,
  `token_recuperacao` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `clinica`
--

INSERT INTO `clinica` (`clinica_id`, `clinica_cnpj`, `clinica_nome_fant`, `clinica_razao_social`, `clinica_inscricao_estadual`, `clinica_inscricao_municipal`, `clinica_telefone`, `clinica_email`, `clinica_endereco`, `clinica_uf`, `clinica_especialidade`, `clinica_senha`, `token_recuperacao`) VALUES
(6, '12345678000190', 'ClÃÂ­nica Vida SaudÃÂ¡vel', 'Vida SaudÃÂ¡vel Medicina Integrada Ltda', '123456789110', '987654', '1134567890', 'clinica.vida@gmail.com', 'Rua das Flores, 123 - Centro, São Paulo - SP', 'SP', 'Cardiologia', '$2y$10$7PLzPIu8.cKy4eO/oLMBzeXvW93G5qY/HayVTCHgYAjka2L21icLe', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `consultas_maior`
--

CREATE TABLE `consultas_maior` (
  `consulta_id` int(11) NOT NULL,
  `medico_id` int(11) NOT NULL,
  `paciente_maior_id` int(11) NOT NULL,
  `data_consulta` date NOT NULL,
  `hora_consulta` time NOT NULL,
  `status` enum('Agendada','Finalizada','Cancelada') NOT NULL DEFAULT 'Agendada',
  `observacoes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `consultas_maior`
--

INSERT INTO `consultas_maior` (`consulta_id`, `medico_id`, `paciente_maior_id`, `data_consulta`, `hora_consulta`, `status`, `observacoes`) VALUES
(1, 1, 6, '2025-10-20', '09:00:00', 'Agendada', 'Dores recorrentes no peito'),
(2, 4, 6, '2025-10-20', '14:00:00', 'Agendada', 'Dores de cabeça'),
(3, 3, 6, '2025-10-18', '08:00:00', 'Finalizada', 'Gravidez'),
(4, 1, 6, '2025-10-10', '10:00:00', 'Finalizada', 'Consulta de rotina - acompanhamento cardíaco'),
(5, 3, 5, '2025-09-15', '14:30:00', 'Finalizada', 'Acompanhamento de gravidez'),
(6, 4, 3, '2025-11-05', '09:15:00', 'Finalizada', 'Avaliação neurológica'),
(7, 1, 7, '2025-12-05', '11:00:00', 'Agendada', 'Check-up anual'),
(8, 3, 8, '2025-12-10', '15:45:00', 'Agendada', 'Consulta ginecológica'),
(9, 4, 9, '2025-11-25', '08:30:00', 'Agendada', 'Avaliação dermatológica'),
(10, 1, 7, '2025-11-28', '16:00:00', 'Agendada', 'Avaliação cardiológica'),
(11, 1, 10, '2025-11-28', '16:45:00', 'Agendada', 'Consulta de rotina - pressão arterial'),
(12, 1, 6, '2025-10-10', '10:00:00', 'Finalizada', 'Consulta de rotina - acompanhamento cardíaco'),
(13, 3, 5, '2025-09-15', '14:30:00', 'Finalizada', 'Acompanhamento de gravidez'),
(14, 4, 3, '2025-11-05', '09:15:00', 'Finalizada', 'Avaliação neurológica'),
(15, 1, 7, '2025-12-05', '11:00:00', 'Agendada', 'Check-up anual'),
(16, 3, 8, '2025-12-10', '15:45:00', 'Agendada', 'Consulta ginecológica'),
(17, 4, 9, '2025-11-25', '08:30:00', 'Agendada', 'Avaliação dermatológica'),
(18, 1, 7, '2025-11-28', '16:00:00', 'Agendada', 'Avaliação cardiológica'),
(19, 1, 10, '2025-11-28', '16:45:00', 'Agendada', 'Consulta de rotina - pressão arterial'),
(20, 1, 6, '2025-10-10', '10:00:00', 'Finalizada', 'Consulta de rotina - acompanhamento cardíaco'),
(21, 3, 5, '2025-09-15', '14:30:00', 'Finalizada', 'Acompanhamento de gravidez'),
(22, 4, 3, '2025-11-05', '09:15:00', 'Finalizada', 'Avaliação neurológica'),
(23, 1, 7, '2025-12-05', '11:00:00', 'Agendada', 'Check-up anual'),
(24, 3, 8, '2025-12-10', '15:45:00', 'Agendada', 'Consulta ginecológica'),
(25, 4, 9, '2025-11-25', '08:30:00', 'Agendada', 'Avaliação dermatológica'),
(26, 1, 7, '2025-11-28', '16:00:00', 'Agendada', 'Avaliação cardiológica'),
(27, 1, 10, '2025-11-28', '16:45:00', 'Agendada', 'Consulta de rotina - pressão arterial');

-- --------------------------------------------------------

--
-- Estrutura para tabela `consultas_menor`
--

CREATE TABLE `consultas_menor` (
  `consulta_id` int(11) NOT NULL,
  `medico_id` int(11) NOT NULL,
  `paciente_menor_id` int(11) NOT NULL,
  `responsavel_id` int(11) NOT NULL,
  `data_consulta` date NOT NULL,
  `hora_consulta` time NOT NULL,
  `status` enum('Agendada','Realizada','Cancelada') NOT NULL DEFAULT 'Agendada',
  `observacoes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `consultas_menor`
--

INSERT INTO `consultas_menor` (`consulta_id`, `medico_id`, `paciente_menor_id`, `responsavel_id`, `data_consulta`, `hora_consulta`, `status`, `observacoes`) VALUES
(1, 4, 2, 6, '2025-10-29', '16:00:00', 'Agendada', 'Dores de cabeça recorrentes'),
(14, 4, 1, 1, '2025-10-20', '10:30:00', 'Realizada', 'Dores de cabeça na escola'),
(15, 1, 1, 1, '2025-11-08', '13:15:00', 'Realizada', 'Check-up pediátrico'),
(16, 1, 2, 6, '2025-12-15', '14:00:00', 'Agendada', 'Consulta pediátrica de rotina'),
(17, 3, 2, 6, '2025-11-30', '11:30:00', 'Agendada', 'Avaliação crescimento'),
(18, 11, 2, 6, '2025-11-28', '17:00:00', 'Agendada', 'Paciente relata crises de enxaqueca 3x por semana há 2 meses, com fotofobia e náuseas. Já fez uso de analgésicos comuns sem melhora significativa.');

-- --------------------------------------------------------

--
-- Estrutura para tabela `farmacia`
--

CREATE TABLE `farmacia` (
  `farmacia_id` int(11) NOT NULL,
  `farmacia_cnpj` char(14) NOT NULL,
  `farmacia_nome_fant` varchar(80) NOT NULL,
  `farmacia_razao_social` varchar(45) NOT NULL,
  `farmacia_inscricao_estadual` char(9) NOT NULL,
  `farmacia_inscricao_municipal` char(15) NOT NULL,
  `farmacia_telefone` varchar(20) NOT NULL,
  `farmacia_email` varchar(100) NOT NULL,
  `farmacia_endereco` varchar(255) NOT NULL,
  `farmacia_uf` varchar(2) NOT NULL,
  `farmacia_cep` varchar(10) NOT NULL,
  `farmacia_senha` varchar(255) NOT NULL,
  `token_recuperacao` varchar(255) DEFAULT NULL,
  `farmacia_foto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `farmacia`
--

INSERT INTO `farmacia` (`farmacia_id`, `farmacia_cnpj`, `farmacia_nome_fant`, `farmacia_razao_social`, `farmacia_inscricao_estadual`, `farmacia_inscricao_municipal`, `farmacia_telefone`, `farmacia_email`, `farmacia_endereco`, `farmacia_uf`, `farmacia_cep`, `farmacia_senha`, `token_recuperacao`, `farmacia_foto`) VALUES
(1, '12345678000199', 'Farma Vida', 'Farma Vida Comercial Ltda', '987654321', '123456', '11987654321', 'atendimento@farmavida.com', 'Av. Brasil, 1234 - Centro', 'SP', '01000000', '', NULL, NULL),
(3, '11111111111193', 'Farma centro', 'Farmácia central', '222222222', '111111111111119', '18997285567', 'farmacia@suporte.com', 'Rua Centro 666', 'CE', '19500008', '', '079d5a03728e56e7901d3c09e3168d7c', NULL),
(4, '55555555555555', 'Drogasil', 'Drogaria do Brasil', '444444444', '999999999999999', '18997284444', 'farm@gmail.com', 'rua das flores', 'SE', '19500000', '', 'fcf7a5717557f891865b8e73fcf6c4e2', NULL),
(5, '45745454863343', 'farmacia roxa', 'drograria', '384983908', '667654456778788', '11912345978', 'damacenojovinomariaclara@gmail.com', 'rua da mota 5', 'AM', '98888888', '$2y$10$NU4Mmeu3aIkRgJ7x/8XzWOB2vLKOLtveKxfhyrr3CyzL13/xkAX6.', NULL, 'farmacia_5_1760719752.png');

-- --------------------------------------------------------

--
-- Estrutura para tabela `medicos`
--

CREATE TABLE `medicos` (
  `medicos_id` int(11) NOT NULL,
  `medicos_crm` char(6) NOT NULL,
  `medicos_uf_crm` varchar(2) NOT NULL,
  `medicos_nome` varchar(100) NOT NULL,
  `medicos_cpf` char(11) NOT NULL,
  `medicos_endereco` varchar(256) NOT NULL,
  `medicos_telefone` varchar(20) NOT NULL,
  `medicos_email` varchar(100) NOT NULL,
  `medicos_especialidade` varchar(100) NOT NULL,
  `medicos_sexo` enum('Masculino','Feminino','Outro') NOT NULL,
  `medicos_datanasc` date NOT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  `medico_senha` varchar(255) NOT NULL,
  `medicos_foto` varchar(255) DEFAULT NULL,
  `token_recuperacao` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `medicos`
--

INSERT INTO `medicos` (`medicos_id`, `medicos_crm`, `medicos_uf_crm`, `medicos_nome`, `medicos_cpf`, `medicos_endereco`, `medicos_telefone`, `medicos_email`, `medicos_especialidade`, `medicos_sexo`, `medicos_datanasc`, `data_cadastro`, `medico_senha`, `medicos_foto`, `token_recuperacao`) VALUES
(1, '111111', 'SP', 'Amanda Cano', '44109772285', 'Rua das Flores', '996518971', 'amanda.medica@gmail.com', 'Cardiologia', 'Feminino', '2000-11-28', '2025-05-30 19:47:50', '', NULL, NULL),
(3, '123456', 'MT', 'marya', '53327061890', 'av lindaura', '18997284482', 'emarya091@gmail.com', 'Geriatria', 'Feminino', '2000-09-02', '2025-06-22 20:23:13', '$2y$10$lb7pBcJi2Ew1dM6kJIBwy.sk/vZ1jUOnSRB56m6QKqDsANWNKPvhG', 'medico_3_1764297940.jpg', NULL),
(4, '332111', '', 'Carlos Andrade', '12345678943', 'Rua das Palmeiras, 120', '11988881001', 'carlos.andrade@gmail.com', 'Neurologia', 'Masculino', '1980-04-12', '2025-10-17 01:35:52', '$2y$10$XcdBGkT5qufNiM47H/Ra3ewq9/0eECK22mw5OJBvYvIYcouX1SDNS', 'medico_4_1760665154.jpg', NULL),
(5, '123457', 'SP', 'João Silva', '12345678901', 'Av. Paulista, 1000 - São Paulo/SP', '11999999991', 'joao.silva@gmail.com', 'Cardiologia', 'Masculino', '1980-05-15', '2025-11-27 23:11:33', '$2y$10$rQ9dZ8kLm1VcEeWqXp5nKuYjJhGtBvN2CwSxRzLb4M7qA6fH8eD', NULL, NULL),
(8, '123460', 'RS', 'Ana Pereira', '45678901234', 'Av. Borges de Medeiros, 500 - Porto Alegre/RS', '51999999994', 'ana.pereira@gmail.com', 'Ginecologia', 'Feminino', '1985-03-25', '2025-11-27 23:11:33', '$2y$10$pQ3rS5tU7vW8xY9zA0b1cD2eF3gH4iJ5kL6mN7oP8qR0sT2uV4wX', NULL, NULL),
(10, '123462', 'PR', 'Juliana Lima', '67890123456', 'Rua XV de Novembro, 200 - Curitiba/PR', '41999999996', 'juliana.lima@gmail.com', 'Oftalmologia', 'Feminino', '1983-11-12', '2025-11-27 23:11:33', '$2y$10$vW8xY9zA0b1cD2eF3gH4iJ5kL6mN7oP8qR9sT0uV1wX2yZ3aB4c', NULL, NULL),
(11, '123463', 'PE', 'Ricardo Alves', '78901234567', 'Av. Conde da Boa Vista, 300 - Recife/PE', '81999999997', 'ricardo.alves@gmail.com', 'Neurologia', 'Masculino', '1976-09-05', '2025-11-27 23:11:33', '$2y$10$dE2fG3hI4jK5lM6nO7pQ8rS9tU0vW1xY2zA3B4c5D6eF7gH8iJ9k', NULL, NULL),
(12, '123464', 'CE', 'Fernanda Rocha', '89012345678', 'Av. Beira Mar, 800 - Fortaleza/CE', '85999999998', 'fernanda.rocha@gmail.com', 'Psiquiatria', 'Feminino', '1981-04-18', '2025-11-27 23:11:33', '$2y$10$hI4jK5lM6nO7pQ8rS9tU0vW1xY2zA3B4c5D6eF7gH8iJ9kL0mN', NULL, NULL),
(13, '123465', 'DF', 'Roberto Souza', '90123456789', 'SQS 104 Bloco B - Brasília/DF', '61999999999', 'roberto.souza@gmail.com', 'Urologia', 'Masculino', '1979-01-22', '2025-11-27 23:11:33', '$2y$10$jK5lM6nO7pQ8rS9tU0vW1xY2zA3B4c5D6eF7gH8iJ9kL0mN1oP', NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `notificacoes`
--

CREATE TABLE `notificacoes` (
  `id` int(11) NOT NULL,
  `medicos_id` int(11) NOT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `titulo` varchar(100) DEFAULT NULL,
  `mensagem` text DEFAULT NULL,
  `lida` tinyint(1) DEFAULT 0,
  `data` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `paciente_maior`
--

CREATE TABLE `paciente_maior` (
  `pmaior_id` int(11) NOT NULL,
  `pmaior_foto` varchar(255) DEFAULT NULL,
  `pmaior_cpf` char(11) NOT NULL,
  `pmaior_nome` varchar(100) NOT NULL,
  `pmaior_endereco` varchar(220) NOT NULL,
  `pmaior_telefone` varchar(20) NOT NULL,
  `pmaior_email` varchar(100) NOT NULL,
  `pmaior_sexo` enum('Masculino','Feminino','Outro') NOT NULL,
  `pmaior_estadocivil` varchar(30) NOT NULL,
  `pmaior_datanasc` date NOT NULL,
  `pmaior_senha` varchar(255) NOT NULL,
  `token_recuperacao` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `paciente_maior`
--

INSERT INTO `paciente_maior` (`pmaior_id`, `pmaior_foto`, `pmaior_cpf`, `pmaior_nome`, `pmaior_endereco`, `pmaior_telefone`, `pmaior_email`, `pmaior_sexo`, `pmaior_estadocivil`, `pmaior_datanasc`, `pmaior_senha`, `token_recuperacao`) VALUES
(1, NULL, '44109772885', 'Amanda Cano', 'Rua das flores', '18997518961', 'amanda@gmail.com', 'Feminino', 'Solteiro', '2000-11-28', '', NULL),
(3, 'paciente_3_1757093225.png', '12345678900', 'Ana Julia da Silva ', 'Rua das Palmeiras, 123, Bairro Verde', '11987654323', 'ana.julia@gmail.com', 'Feminino', 'Solteiro', '1990-05-15', '', NULL),
(4, 'paciente_4_1757092242.png', '77711177777', 'Ricardo Cano', 'Rua dos Ipês, 536', '18991336696', 'ricardo.cano@gmail.com', 'Masculino', 'Casado', '1980-04-30', '', '605d3cc5844217b7cca6c3b0f17bce85'),
(5, 'paciente_5_1758906575.jpg', '12387917199', 'Ana Lúcia de Almeida', 'Rua das Flores, 45, Aap.102', '12121111444', 'ana.lucia@gmail.com', 'Feminino', 'Divorciado', '1992-06-30', '$2y$10$0WmjzRei6hAD1Hz5bjc0R.eDNTscFkv7Kjf0QqgM7ityqs/e6lzTO', NULL),
(6, 'paciente_6_1759509653.jpg', '12345678905', 'Maria Silva Santos', 'Rua das Acácias, 123 - Jardim Paulista, São Paulo - SP', '18881772226', 'maria.silva@gmail.com', 'Feminino', 'Casado', '1985-03-15', '$2y$10$Szx7JldM6z6ZLX/LyB4R4elQlqgp6yDn9i691lQkBU./5tKyqyQNi', NULL),
(7, NULL, '98765432100', 'Lucas Oliveira', 'Rua das Palmeiras, 123 - Jardins, São Paulo/SP', '11988887777', 'lucas.oliveira@gmail.com', 'Masculino', 'Solteiro', '1990-03-15', '$2y$10$rQ9dZ8kLm1VcEeWqXp5nKuYjJhGtBvN2CwSxRzLb4M7qA6fH8eD', NULL),
(8, NULL, '87654321098', 'Camila Santos', 'Av. Brasil, 456 - Centro, Rio de Janeiro/RJ', '21977776666', 'camila.santos@gmail.com', 'Feminino', 'Casado', '1985-07-22', '$2y$10$sT1eF6gH9jK2lM3nO5p7QrS8tU0vW1xY3zA4B6c8D0eF2gH4jL6n', NULL),
(9, NULL, '76543210987', 'Rafael Costa', 'Rua das Flores, 789 - Bela Vista, Belo Horizonte/MG', '31966665555', 'rafael.costa@gmail.com', 'Masculino', 'Divorciado', '1978-11-30', '$2y$10$uV2wX4yZ5aB6c7d8eF0gHiJkL1mN2oP3qR4sT6u8V0wX2yZ4aB6c', NULL),
(10, NULL, '65432109876', 'Isabela Rodrigues', 'Av. Paulista, 1001 - Cerqueira César, São Paulo/SP', '11955554444', 'isabela.rodrigues@gmail.com', 'Feminino', 'Solteiro', '1995-05-10', '$2y$10$pQ3rS5tU7vW8xY9zA0b1cD2eF3gH4iJ5kL6mN7oP8qR0sT2uV4wX', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `paciente_menor`
--

CREATE TABLE `paciente_menor` (
  `pmenor_id` int(11) NOT NULL,
  `pmaior_id` int(11) NOT NULL,
  `pmenor_cpf` char(11) NOT NULL,
  `pmenor_nome` varchar(100) NOT NULL,
  `pmenor_endereco` varchar(220) NOT NULL,
  `pmenor_telefone` varchar(20) NOT NULL,
  `pmenor_email` varchar(100) NOT NULL,
  `pmenor_sexo` enum('Masculino','Feminino','Outro') NOT NULL,
  `pmenor_estadocivil` varchar(30) NOT NULL,
  `pmenor_datanasc` date NOT NULL,
  `pmenor_foto` varchar(255) DEFAULT NULL,
  `pmenor_senha` varchar(255) NOT NULL,
  `token_recuperacao` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `paciente_menor`
--

INSERT INTO `paciente_menor` (`pmenor_id`, `pmaior_id`, `pmenor_cpf`, `pmenor_nome`, `pmenor_endereco`, `pmenor_telefone`, `pmenor_email`, `pmenor_sexo`, `pmenor_estadocivil`, `pmenor_datanasc`, `pmenor_foto`, `pmenor_senha`, `token_recuperacao`) VALUES
(1, 1, '44110977289', 'caik cano', 'rua do limoeiro', '11909876513', 'caiquicano@gmail.com', 'Masculino', 'Solteiro', '2020-12-10', NULL, '$2y$10$6tgDsGBnswChirPIj6XY9.McBWsgs77IXrE02FxNqdTsZp0z/PMWq', 'e1c8d86b7a00db0b4acf0796de0fe8e2'),
(2, 6, '11122233344', 'Maria Oliveira Silva', 'Rua das Flores, 123 - Centro, São Paulo - SP', '11987654311', 'maria.oliveira@gmail.com', 'Feminino', 'Solteiro', '2009-03-15', 'paciente_menor_2_1760703487.jpg', '$2y$10$GD/vVm7T54QbdTArI/Y2pOgrFd7KZs/30A.DI6x94zh067krckjHK', NULL),
(7, 7, '33344455566', 'Pedro Oliveira', 'Rua das Palmeiras, 123 - Jardins, São Paulo/SP', '11988887778', 'pedro.oliveira@gmail.com', 'Masculino', 'Solteiro', '2015-08-10', NULL, '$2y$10$rQ9dZ8kLm1VcEeWqXp5nKuYjJhGtBvN2CwSxRzLb4M7qA6fH8eD', NULL),
(8, 8, '44455566677', 'Sophia Santos', 'Av. Brasil, 456 - Centro, Rio de Janeiro/RJ', '21977776667', 'sophia.santos@gmail.com', 'Feminino', 'Solteiro', '2018-03-25', NULL, '$2y$10$sT1eF6gH9jK2lM3nO5p7QrS8tU0vW1xY3zA4B6c8D0eF2gH4jL6n', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `receita`
--

CREATE TABLE `receita` (
  `receita_id` int(11) NOT NULL,
  `paciente_maior_id` int(11) NOT NULL,
  `medico_id` int(11) NOT NULL,
  `data_emissao` date NOT NULL,
  `validade` date NOT NULL,
  `validade_final` date DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `paciente_menor_id` int(11) DEFAULT NULL,
  `tipo_paciente` enum('maior','menor') DEFAULT 'maior',
  `status_receita` varchar(20) DEFAULT 'valida'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `receita`
--

INSERT INTO `receita` (`receita_id`, `paciente_maior_id`, `medico_id`, `data_emissao`, `validade`, `validade_final`, `observacoes`, `paciente_menor_id`, `tipo_paciente`, `status_receita`) VALUES
(1, 6, 3, '2025-10-17', '2025-10-17', '2026-01-17', 'Ácido Fólico 400microgramas p/dia durante três meses\r\n\r\n', NULL, 'maior', 'valida'),
(17, 6, 1, '2025-10-10', '2025-10-10', '2026-01-10', 'Losartana 50mg - 1 comprimido pela manhã\nAAS 100mg - 1 comprimido ao dia\nAtorvastatina 20mg - 1 comprimido à noite\nPropranolol 40mg - 1 comprimido 2x ao dia', NULL, 'maior', 'valida'),
(18, 5, 3, '2025-09-15', '2025-09-15', '2026-03-15', 'Ácido Fólico 400mcg - 1 comprimido ao dia\nSulfato Ferroso 40mg - 1 comprimido ao dia\nCarbonato de Cálcio 500mg - 1 comprimido 2x ao dia\nVitamina D 2000UI - 1 cápsula ao dia', NULL, 'maior', 'valida'),
(19, 3, 4, '2025-11-05', '2025-11-05', '2026-02-05', 'Topiramato 50mg - 1 comprimido 2x ao dia\nPropranolol 40mg - 1 comprimido 2x ao dia\nParacetamol 500mg - 1 comprimido a cada 6h se dor\nDiazepam 5mg - 1/2 comprimido à noite se ansiedade', NULL, 'maior', 'valida'),
(20, 7, 1, '2025-11-28', '2025-11-28', '2026-02-28', 'Enalapril 10mg - 1 comprimido ao dia\nHidroclorotiazida 25mg - 1 comprimido ao dia\nSinvastatina 20mg - 1 comprimido à noite\nAAS 100mg - 1 comprimido ao dia', NULL, 'maior', 'valida'),
(24, 6, 1, '2025-10-10', '2025-10-10', '2026-01-10', 'Losartana 50mg - 1 comprimido pela manhã\nAAS 100mg - 1 comprimido ao dia', NULL, 'maior', 'valida'),
(25, 5, 3, '2025-09-15', '2025-09-15', '2026-03-15', 'Ácido Fólico 400mcg - 1 comprimido ao dia\nSulfato Ferroso 40mg - 1 comprimido ao dia', NULL, 'maior', 'valida'),
(26, 3, 4, '2025-11-05', '2025-11-05', '2026-02-05', 'Topiramato 50mg - 1 comprimido 2x ao dia\nPropranolol 40mg - 1 comprimido 2x ao dia', NULL, 'maior', 'valida'),
(30, 6, 1, '2025-10-10', '2025-10-10', '2026-01-10', 'Losartana 50mg - 1 comprimido pela manhã', NULL, 'maior', 'valida'),
(31, 5, 3, '2025-09-15', '2025-09-15', '2026-03-15', 'Ácido Fólico 400mcg - 1 comprimido ao dia', NULL, 'maior', 'valida'),
(32, 3, 4, '2025-11-05', '2025-11-05', '2026-02-05', 'Topiramato 50mg - 1 comprimido 2x ao dia', NULL, 'maior', 'valida'),
(33, 6, 4, '2025-11-28', '2025-11-28', '2026-02-28', 'Fluxon 10mg (Flunarizina) - 1 comprimido ao dia à noite (profilaxia da enxaqueca)\nSumax 50mg (Sumatriptana) - 1 comprimido na crise (máximo 2 comprimidos por dia)\nParacetamol 750mg - 1 comprimido a cada 6 horas se dor leve\nOmeprazol 20mg - 1 cápsula ao dia em jejum (proteção gástrica)', 2, 'menor', 'valida');

-- --------------------------------------------------------

--
-- Estrutura para tabela `remedios`
--

CREATE TABLE `remedios` (
  `id_remedio` int(11) NOT NULL,
  `nome_remedio` varchar(100) NOT NULL,
  `principio_ativo` varchar(100) NOT NULL,
  `dosagem` varchar(50) DEFAULT NULL,
  `forma_farmaceutica` varchar(50) DEFAULT NULL,
  `quantidade` int(11) NOT NULL,
  `registro_anvisa` varchar(20) DEFAULT NULL,
  `farmacia_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `remedios`
--

INSERT INTO `remedios` (`id_remedio`, `nome_remedio`, `principio_ativo`, `dosagem`, `forma_farmaceutica`, `quantidade`, `registro_anvisa`, `farmacia_id`) VALUES
(1, 'Paracetamol 500mg', 'Paracetamol', '500mg', 'Comprimido', 20, '123456789', 0),
(2, 'Dipirona 500mg', 'Dipirona', '500mg', 'Comprimido', 30, '234567890', 0),
(3, 'Ibuprofeno 200mg', 'Ibuprofeno', '200mg', 'Cápsula', 10, '345678901', 0),
(4, 'Amoxicilina 500mg', 'Amoxicilina', '500mg', 'Cápsula', 12, '456789012', 0),
(5, 'Losartana 50mg', 'Losartana', '50mg', 'Comprimido', 30, '567890123', 0),
(6, 'Omeprazol 20mg', 'Omeprazol', '20mg', 'Cápsula', 14, '678901234', 0),
(7, 'Simvastatina 20mg', 'Simvastatina', '20mg', 'Comprimido', 30, '789012345', 0),
(8, 'Loratadina 10mg', 'Loratadina', '10mg', 'Comprimido', 20, '890123456', 0),
(9, 'Cetirizina 10mg', 'Cetirizina', '10mg', 'Comprimido', 15, '901234567', 0),
(10, 'Ranitidina 150mg', 'Ranitidina', '150mg', 'Comprimido', 30, '123456788', 0),
(11, 'Metformina 500mg', 'Metformina', '500mg', 'Comprimido', 30, '234567889', 0),
(12, 'Hidroclorotiazida 25mg', 'Hidroclorotiazida', '25mg', 'Comprimido', 20, '345678900', 0),
(13, 'Atorvastatina 40mg', 'Atorvastatina', '40mg', 'Comprimido', 30, '456789011', 0),
(14, 'Clonazepam 2mg', 'Clonazepam', '2mg', 'Comprimido', 20, '567890122', 0),
(15, 'AAS 500mg', 'Ácido Acetilsalicílico', '500mg', 'Comprimido', 20, '678901233', 0),
(16, 'Furosemida 40mg', 'Furosemida', '40mg', 'Comprimido', 30, '789012344', 0),
(17, 'Fluoxetina 20mg', 'Fluoxetina', '20mg', 'Cápsula', 28, '890123457', 0),
(18, 'Propranolol 40mg', 'Propranolol', '40mg', 'Comprimido', 30, '901234568', 0),
(19, 'Hidroxicloroquina 200mg', 'Hidroxicloroquina', '200mg', 'Comprimido', 20, '123456787', 0),
(20, 'Codeína 30mg', 'Codeína', '30mg', 'Comprimido', 15, '234567888', 0),
(22, 'Cloroquina 250mg', 'Cloroquina', '250mg', 'Comprimido', 12, '456789010', 0),
(23, 'Metoclopramida 10mg', 'Metoclopramida', '10mg', 'Comprimido', 25, '567890121', 0),
(24, 'Escitalopram 10mg', 'Escitalopram', '10mg', 'Comprimido', 30, '678901232', 0),
(25, 'Enalapril 10mg', 'Enalapril', '10mg', 'Comprimido', 28, '789012343', 0),
(26, 'Captopril 25mg', 'Captopril', '25mg', 'Comprimido', 30, '890123454', 0),
(27, 'Dexametasona 0.5mg', 'Dexametasona', '0.5mg', 'Comprimido', 20, '901234565', 0),
(28, 'Salbutamol 2mg', 'Salbutamol', '2mg', 'Comprimido', 20, '123456786', 0),
(29, 'Tamsulosina 0.4mg', 'Tamsulosina', '0.4mg', 'Cápsula', 30, '234567877', 0),
(30, 'Vitamina D 1000UI', 'Colecalciferol', '1000UI', 'Cápsula', 30, '345678888', 0),
(0, 'Tylenol 750mg', 'Paracetamol', '750mg', 'Comprimido', 50, '12345678901234', 5),
(0, 'Dorflex', 'Dipirona Monoidratada', '300mg', 'Comprimido', 80, '23456789012345', 5),
(0, 'Ibuprofeno 400mg', 'Ibuprofeno', '400mg', 'Comprimido', 45, '34567890123456', 5),
(0, 'Nimesulida 100mg', 'Nimesulida', '100mg', 'Comprimido', 30, '45678901234567', 5),
(0, 'Cetoprofeno 100mg', 'Cetoprofeno', '100mg', 'Comprimido', 25, '56789012345678', 5),
(0, 'Amoxicilina 500mg', 'Amoxicilina', '500mg', 'Cápsula', 60, '67890123456789', 5),
(0, 'Azitromicina 500mg', 'Azitromicina', '500mg', 'Comprimido', 40, '78901234567890', 5),
(0, 'Losartana 50mg', 'Losartana Potássica', '50mg', 'Comprimido', 100, '89012345678901', 5),
(0, 'Atenolol 50mg', 'Atenolol', '50mg', 'Comprimido', 75, '90123456789012', 5),
(0, 'Omeprazol 20mg', 'Omeprazol', '20mg', 'Cápsula', 90, '01234567890123', 5),
(0, 'Dramin', 'Dimenidrato', '50mg', 'Comprimido', 55, '11223344556677', 5),
(0, 'Loratadina 10mg', 'Loratadina', '10mg', 'Comprimido', 65, '22334455667788', 5),
(0, 'Allegra 180mg', 'Fexofenadina', '180mg', 'Comprimido', 35, '33445566778899', 5),
(0, 'Metformina 850mg', 'Metformina', '850mg', 'Comprimido', 120, '44556677889900', 5),
(0, 'Glibenclamida 5mg', 'Glibenclamida', '5mg', 'Comprimido', 70, '55667788990011', 5),
(0, 'Sinvastatina 20mg', 'Sinvastatina', '20mg', 'Comprimido', 85, '66778899001122', 5),
(0, 'Atorvastatina 20mg', 'Atorvastatina Cálcica', '20mg', 'Comprimido', 60, '77889900112233', 5),
(0, 'Vitamina C 1g', 'Ácido Ascórbico', '1000mg', 'Comprimido', 150, '88990011223344', 5),
(0, 'Vitamina D 2000UI', 'Colecalciferol', '2000UI', 'Cápsula', 95, '99001122334455', 5);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `administradores`
--
ALTER TABLE `administradores`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `admin_email` (`admin_email`);

--
-- Índices de tabela `atestado`
--
ALTER TABLE `atestado`
  ADD PRIMARY KEY (`atestado_id`),
  ADD KEY `atestado_medico_fk` (`medico_id`),
  ADD KEY `atestado_pmaior_fk` (`paciente_maior_id`),
  ADD KEY `atestado_pmenor_fk` (`paciente_menor_id`,`responsavel_id`);

--
-- Índices de tabela `clinica`
--
ALTER TABLE `clinica`
  ADD PRIMARY KEY (`clinica_id`),
  ADD UNIQUE KEY `clinica_cnpj` (`clinica_cnpj`);

--
-- Índices de tabela `consultas_maior`
--
ALTER TABLE `consultas_maior`
  ADD PRIMARY KEY (`consulta_id`),
  ADD KEY `consultas_maior_medico_fk` (`medico_id`),
  ADD KEY `consultas_maior_paciente_fk` (`paciente_maior_id`);

--
-- Índices de tabela `consultas_menor`
--
ALTER TABLE `consultas_menor`
  ADD PRIMARY KEY (`consulta_id`),
  ADD KEY `consultas_menor_medico_fk` (`medico_id`),
  ADD KEY `consultas_menor_paciente_menor_fk` (`paciente_menor_id`,`responsavel_id`);

--
-- Índices de tabela `farmacia`
--
ALTER TABLE `farmacia`
  ADD PRIMARY KEY (`farmacia_id`),
  ADD UNIQUE KEY `farmacia_email_UNIQUE` (`farmacia_email`),
  ADD UNIQUE KEY `farmacia_telefone_UNIQUE` (`farmacia_telefone`),
  ADD UNIQUE KEY `farmacia_inscricao_municipal_UNIQUE` (`farmacia_inscricao_municipal`),
  ADD UNIQUE KEY `farmacia_inscricao_estadual_UNIQUE` (`farmacia_inscricao_estadual`),
  ADD UNIQUE KEY `farmacia_cnpj_UNIQUE` (`farmacia_cnpj`);

--
-- Índices de tabela `medicos`
--
ALTER TABLE `medicos`
  ADD PRIMARY KEY (`medicos_id`),
  ADD UNIQUE KEY `medicos_crm_UNIQUE` (`medicos_crm`),
  ADD UNIQUE KEY `medicos_telefone_UNIQUE` (`medicos_telefone`),
  ADD UNIQUE KEY `medicos_cpf_UNIQUE` (`medicos_cpf`),
  ADD UNIQUE KEY `medicos_email_UNIQUE` (`medicos_email`);

--
-- Índices de tabela `paciente_maior`
--
ALTER TABLE `paciente_maior`
  ADD PRIMARY KEY (`pmaior_id`),
  ADD UNIQUE KEY `pmaior_cpf_UNIQUE` (`pmaior_cpf`),
  ADD UNIQUE KEY `pmaior_telefone_UNIQUE` (`pmaior_telefone`),
  ADD UNIQUE KEY `pmaior_email_UNIQUE` (`pmaior_email`);

--
-- Índices de tabela `paciente_menor`
--
ALTER TABLE `paciente_menor`
  ADD PRIMARY KEY (`pmenor_id`,`pmaior_id`),
  ADD UNIQUE KEY `pmenor_cpf_UNIQUE` (`pmenor_cpf`),
  ADD UNIQUE KEY `pmenor_telefone_UNIQUE` (`pmenor_telefone`),
  ADD UNIQUE KEY `pmenor_email_UNIQUE` (`pmenor_email`),
  ADD KEY `fk_paciente_menor_paciente_maior_idx` (`pmaior_id`);

--
-- Índices de tabela `receita`
--
ALTER TABLE `receita`
  ADD PRIMARY KEY (`receita_id`),
  ADD KEY `paciente_maior_id` (`paciente_maior_id`),
  ADD KEY `medico_id` (`medico_id`),
  ADD KEY `fk_receita_menor` (`paciente_menor_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `administradores`
--
ALTER TABLE `administradores`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `atestado`
--
ALTER TABLE `atestado`
  MODIFY `atestado_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de tabela `clinica`
--
ALTER TABLE `clinica`
  MODIFY `clinica_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `consultas_maior`
--
ALTER TABLE `consultas_maior`
  MODIFY `consulta_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de tabela `consultas_menor`
--
ALTER TABLE `consultas_menor`
  MODIFY `consulta_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de tabela `farmacia`
--
ALTER TABLE `farmacia`
  MODIFY `farmacia_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `medicos`
--
ALTER TABLE `medicos`
  MODIFY `medicos_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de tabela `paciente_maior`
--
ALTER TABLE `paciente_maior`
  MODIFY `pmaior_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `paciente_menor`
--
ALTER TABLE `paciente_menor`
  MODIFY `pmenor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `receita`
--
ALTER TABLE `receita`
  MODIFY `receita_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `atestado`
--
ALTER TABLE `atestado`
  ADD CONSTRAINT `atestado_medico_fk` FOREIGN KEY (`medico_id`) REFERENCES `medicos` (`medicos_id`),
  ADD CONSTRAINT `atestado_pmaior_fk` FOREIGN KEY (`paciente_maior_id`) REFERENCES `paciente_maior` (`pmaior_id`),
  ADD CONSTRAINT `atestado_pmenor_fk` FOREIGN KEY (`paciente_menor_id`,`responsavel_id`) REFERENCES `paciente_menor` (`pmenor_id`, `pmaior_id`);

--
-- Restrições para tabelas `consultas_maior`
--
ALTER TABLE `consultas_maior`
  ADD CONSTRAINT `consultas_maior_medico_fk` FOREIGN KEY (`medico_id`) REFERENCES `medicos` (`medicos_id`),
  ADD CONSTRAINT `consultas_maior_paciente_fk` FOREIGN KEY (`paciente_maior_id`) REFERENCES `paciente_maior` (`pmaior_id`);

--
-- Restrições para tabelas `consultas_menor`
--
ALTER TABLE `consultas_menor`
  ADD CONSTRAINT `consultas_menor_medico_fk` FOREIGN KEY (`medico_id`) REFERENCES `medicos` (`medicos_id`),
  ADD CONSTRAINT `consultas_menor_paciente_menor_fk` FOREIGN KEY (`paciente_menor_id`,`responsavel_id`) REFERENCES `paciente_menor` (`pmenor_id`, `pmaior_id`);

--
-- Restrições para tabelas `paciente_menor`
--
ALTER TABLE `paciente_menor`
  ADD CONSTRAINT `fk_paciente_menor_paciente_maior` FOREIGN KEY (`pmaior_id`) REFERENCES `paciente_maior` (`pmaior_id`);

--
-- Restrições para tabelas `receita`
--
ALTER TABLE `receita`
  ADD CONSTRAINT `fk_receita_menor` FOREIGN KEY (`paciente_menor_id`) REFERENCES `paciente_menor` (`pmenor_id`),
  ADD CONSTRAINT `receita_ibfk_1` FOREIGN KEY (`paciente_maior_id`) REFERENCES `paciente_maior` (`pmaior_id`),
  ADD CONSTRAINT `receita_ibfk_2` FOREIGN KEY (`medico_id`) REFERENCES `medicos` (`medicos_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
