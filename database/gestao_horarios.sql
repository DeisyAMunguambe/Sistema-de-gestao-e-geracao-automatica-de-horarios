-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 13, 2026 at 11:34 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gestao_horarios`
--

-- --------------------------------------------------------

--
-- Table structure for table `bloco_de_salas`
--

CREATE TABLE `bloco_de_salas` (
  `id_bloco` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `id_departamento` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bloco_de_salas`
--

INSERT INTO `bloco_de_salas` (`id_bloco`, `nome`, `id_departamento`) VALUES
(1, 'Bloco A', 1),
(2, 'Bloco B', 4),
(3, 'Bloco C', 2),
(6, 'Bloco D', 3);

-- --------------------------------------------------------

--
-- Table structure for table `curso`
--

CREATE TABLE `curso` (
  `id_curso` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `id_departamento` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `curso`
--

INSERT INTO `curso` (`id_curso`, `nome`, `id_departamento`) VALUES
(11, 'Construção Civil', 4),
(5, 'Contabilidade', 2),
(2, 'Desenvolvimento de Sistemas', 1),
(8, 'Eletricidade Industrial', 3),
(3, 'Gestão', 2),
(4, 'Gestão de Logística', 2),
(6, 'Gestão de Recursos Humanos', 2),
(7, 'Gestão Patrimonial e Financeira', 2),
(10, 'Técnico de Administração de Sistemas e Redes', 1),
(1, 'Técnico de Programação Web', 1),
(9, 'Técnico de Suporte Informático', 1);

-- --------------------------------------------------------

--
-- Table structure for table `departamento`
--

CREATE TABLE `departamento` (
  `id_departamento` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departamento`
--

INSERT INTO `departamento` (`id_departamento`, `nome`) VALUES
(4, 'Construção Civil'),
(3, 'Eletricidade'),
(2, 'Gestão'),
(1, 'Tecnologias de Informação');

-- --------------------------------------------------------

--
-- Table structure for table `disponibilidade`
--

CREATE TABLE `disponibilidade` (
  `id_disponibilidade` int(11) NOT NULL,
  `id_formador` int(11) NOT NULL,
  `dia_semana` enum('Segunda','Terca','Quarta','Quinta','Sexta','Sabado') NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fim` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `disponibilidade`
--

INSERT INTO `disponibilidade` (`id_disponibilidade`, `id_formador`, `dia_semana`, `hora_inicio`, `hora_fim`) VALUES
(1, 1, 'Segunda', '17:00:00', '20:55:00'),
(4, 1, 'Terca', '17:00:00', '20:55:00'),
(7, 1, 'Quarta', '17:00:00', '20:55:00'),
(10, 1, 'Quinta', '17:00:00', '20:55:00'),
(13, 1, 'Sexta', '17:00:00', '20:55:00'),
(16, 1, 'Sabado', '08:35:00', '12:40:00'),
(2, 2, 'Segunda', '17:00:00', '20:55:00'),
(5, 2, 'Terca', '17:00:00', '20:55:00'),
(8, 2, 'Quarta', '17:00:00', '20:55:00'),
(11, 2, 'Quinta', '17:00:00', '20:55:00'),
(14, 2, 'Sexta', '17:00:00', '20:55:00'),
(17, 2, 'Sabado', '08:35:00', '12:40:00'),
(3, 3, 'Segunda', '17:00:00', '20:55:00'),
(6, 3, 'Terca', '17:00:00', '20:55:00'),
(9, 3, 'Quarta', '17:00:00', '20:55:00'),
(12, 3, 'Quinta', '17:00:00', '20:55:00'),
(15, 3, 'Sexta', '17:00:00', '20:55:00'),
(18, 3, 'Sabado', '08:35:00', '12:40:00'),
(20, 6, 'Segunda', '17:00:00', '20:55:00'),
(21, 6, 'Terca', '17:00:00', '20:55:00'),
(22, 6, 'Quarta', '17:00:00', '20:55:00'),
(23, 6, 'Quinta', '17:00:00', '20:55:00'),
(24, 6, 'Sexta', '17:00:00', '20:55:00'),
(25, 6, 'Sabado', '17:00:00', '20:55:00'),
(39, 7, 'Segunda', '17:00:00', '20:55:00'),
(40, 7, 'Terca', '17:00:00', '20:55:00'),
(41, 7, 'Quarta', '17:00:00', '20:55:00'),
(42, 7, 'Sexta', '17:00:00', '20:55:00'),
(60, 9, 'Segunda', '17:00:00', '20:55:00'),
(62, 9, 'Terca', '17:00:00', '20:55:00'),
(61, 9, 'Quarta', '17:00:00', '20:55:00'),
(63, 9, 'Quinta', '17:00:00', '20:55:00'),
(64, 9, 'Sexta', '17:00:00', '20:55:00'),
(50, 10, 'Segunda', '17:00:00', '20:55:00'),
(51, 10, 'Terca', '17:00:00', '20:55:00'),
(52, 10, 'Quarta', '17:00:00', '20:55:00'),
(53, 10, 'Quinta', '17:00:00', '20:55:00'),
(54, 10, 'Sexta', '17:00:00', '20:55:00'),
(44, 11, 'Segunda', '17:00:00', '20:55:00'),
(45, 11, 'Terca', '17:00:00', '20:55:00'),
(46, 11, 'Quarta', '17:00:00', '20:55:00'),
(49, 11, 'Quinta', '17:00:00', '20:55:00'),
(48, 11, 'Sexta', '17:00:00', '20:55:00'),
(55, 12, 'Segunda', '17:00:00', '20:55:00'),
(56, 12, 'Terca', '17:00:00', '20:55:00'),
(58, 12, 'Quarta', '17:00:00', '20:55:00'),
(57, 12, 'Quinta', '17:00:00', '20:55:00'),
(59, 12, 'Sexta', '17:00:00', '20:55:00'),
(26, 13, 'Segunda', '17:00:00', '20:55:00'),
(27, 13, 'Terca', '17:00:00', '20:55:00'),
(28, 13, 'Quarta', '17:00:00', '20:55:00'),
(29, 13, 'Quinta', '17:00:00', '20:55:00'),
(31, 13, 'Sexta', '17:00:00', '20:55:00'),
(34, 14, 'Segunda', '17:00:00', '20:55:00'),
(35, 14, 'Terca', '17:00:00', '20:55:00'),
(33, 14, 'Quarta', '17:00:00', '20:55:00'),
(36, 14, 'Quinta', '17:00:00', '20:55:00'),
(37, 14, 'Sexta', '17:00:00', '20:55:00'),
(38, 14, 'Sabado', '17:00:00', '20:55:00'),
(65, 15, 'Segunda', '17:00:00', '20:55:00'),
(66, 15, 'Terca', '17:00:00', '20:55:00'),
(67, 15, 'Quarta', '17:00:00', '20:55:00'),
(69, 15, 'Quinta', '17:00:00', '20:55:00'),
(68, 15, 'Sexta', '17:00:00', '20:55:00'),
(70, 16, 'Segunda', '17:00:00', '20:55:00'),
(74, 16, 'Terca', '17:00:00', '20:55:00'),
(71, 16, 'Quarta', '17:00:00', '20:55:00'),
(72, 16, 'Quinta', '17:00:00', '20:55:00'),
(73, 16, 'Sexta', '17:00:00', '20:55:00'),
(75, 21, 'Quarta', '17:00:00', '20:55:00'),
(77, 21, 'Sexta', '17:00:00', '20:55:00'),
(78, 22, 'Terca', '17:00:00', '20:55:00'),
(79, 22, 'Quarta', '17:00:00', '20:55:00');

-- --------------------------------------------------------

--
-- Table structure for table `formador`
--

CREATE TABLE `formador` (
  `id_formador` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `formador`
--

INSERT INTO `formador` (`id_formador`, `nome`, `email`, `telefone`) VALUES
(1, 'Afzal Cuinhane', 'afzal@gmail.com', '840000001'),
(2, 'Barbione Augusto', 'barbione@gmail.com', '840000002'),
(3, 'Fernando Langa', 'fernando@escola.local', '840000003'),
(4, 'Estevao Nhane', 'nhanecasa@gmail.com', '847228143'),
(5, 'Helena Lopes', 'helenalopes@gmil.com', '840000005'),
(6, 'António Manuel', 'antonio.manuel@gmail.com', '841234501'),
(7, 'Carlos Alberto', 'carlos.alberto@gmail.com', '841234502'),
(8, 'Fernando Joaquim', 'fernando.joaquim@gmail.com', '841234503'),
(9, 'João Pedro', 'joao.pedro@gmail.com', '841234504'),
(10, 'Manuel Ernesto', 'manuel.ernesto@gmail.com', '841234505'),
(11, 'Paulo Alexandre', 'paulo.alexandre@gmail.com', '841234506'),
(12, 'José Carlos', 'jose.carlos@gmail.com', '841234507'),
(13, 'Armando Luís', 'armando.luis@gmail.com', '841234508'),
(14, 'Bernardo António', 'bernardo.antonio@gmail.com', '841234509'),
(15, 'Domingos Mateus', 'domingos.mateus@gmail.com', '841234510'),
(16, 'Eduardo Filipe', 'eduardo.filipe@gmail.com', '841234511'),
(17, 'Gabriel Augusto', 'gabriel.augusto@gmail.com', '841234512'),
(18, 'Henrique Salvador', 'henrique.salvador@gmail.com', '841234513'),
(19, 'Ismael Francisco', 'ismael.francisco@gmail.com', '841234514'),
(20, 'Lucas Adriano', 'lucas.adriano@gmail.com', '841234515'),
(21, 'Ana Tembe', 'anatembe@gmail.com', '843335422'),
(22, 'Manuela Narciso', 'nanu@gmail.com', '843362524');

-- --------------------------------------------------------

--
-- Table structure for table `for_mod`
--

CREATE TABLE `for_mod` (
  `id_formador` int(11) NOT NULL,
  `id_modulo` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `for_mod`
--

INSERT INTO `for_mod` (`id_formador`, `id_modulo`) VALUES
(1, 1),
(1, 5),
(1, 23),
(2, 2),
(2, 4),
(3, 3),
(4, 6),
(6, 1),
(6, 3),
(6, 6),
(6, 21),
(6, 23),
(7, 1),
(7, 16),
(7, 21),
(8, 1),
(8, 18),
(8, 22),
(9, 2),
(9, 7),
(9, 22),
(10, 2),
(10, 10),
(11, 2),
(11, 9),
(12, 3),
(12, 8),
(13, 3),
(13, 11),
(14, 3),
(14, 13),
(15, 4),
(15, 17),
(16, 4),
(16, 12),
(17, 4),
(17, 14),
(18, 5),
(18, 15),
(19, 5),
(19, 19),
(20, 5),
(20, 20),
(21, 1),
(21, 2),
(21, 3),
(21, 6),
(21, 7),
(21, 18),
(21, 19),
(21, 21),
(21, 22),
(21, 23),
(22, 1),
(22, 2),
(22, 6),
(22, 16),
(22, 17),
(22, 18),
(22, 19),
(22, 21),
(22, 22),
(22, 23);

-- --------------------------------------------------------

--
-- Table structure for table `horario`
--

CREATE TABLE `horario` (
  `id_horario` int(11) NOT NULL,
  `id_turma` int(11) NOT NULL,
  `id_modulo` int(11) NOT NULL,
  `id_formador` int(11) NOT NULL,
  `id_sala` int(11) NOT NULL,
  `dia_semana` enum('Segunda','Terca','Quarta','Quinta','Sexta','Sabado') NOT NULL,
  `turno` enum('manha','tarde','noite') NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fim` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `horario`
--

INSERT INTO `horario` (`id_horario`, `id_turma`, `id_modulo`, `id_formador`, `id_sala`, `dia_semana`, `turno`, `hora_inicio`, `hora_fim`) VALUES
(6, 1, 1, 1, 2, 'Segunda', 'noite', '17:00:00', '20:55:00'),
(7, 1, 2, 2, 2, 'Terca', 'noite', '17:00:00', '20:55:00'),
(8, 1, 3, 3, 2, 'Quarta', 'noite', '17:00:00', '20:55:00'),
(9, 1, 4, 2, 1, 'Quinta', 'noite', '17:00:00', '20:55:00'),
(10, 1, 5, 1, 2, 'Sexta', 'noite', '17:00:00', '20:55:00'),
(11, 2, 6, 6, 1, 'Segunda', 'noite', '17:00:00', '20:10:00'),
(12, 2, 1, 1, 3, 'Terca', 'noite', '17:00:00', '20:10:00'),
(13, 2, 1, 1, 2, 'Quinta', 'noite', '17:00:00', '20:10:00'),
(14, 2, 7, 21, 1, 'Quarta', 'noite', '17:00:00', '20:10:00'),
(15, 4, 6, 4, 9, 'Segunda', 'noite', '17:00:00', '20:10:00'),
(16, 4, 1, 6, 4, 'Terca', 'noite', '17:00:00', '20:10:00'),
(17, 4, 1, 6, 3, 'Quinta', 'noite', '17:00:00', '20:10:00'),
(18, 4, 7, 9, 9, 'Quarta', 'noite', '17:00:00', '20:10:00');

-- --------------------------------------------------------

--
-- Table structure for table `horario_ciclo`
--

CREATE TABLE `horario_ciclo` (
  `id_ciclo` int(11) NOT NULL,
  `id_turma` int(11) NOT NULL,
  `numero` int(11) NOT NULL,
  `data_inicio` date NOT NULL,
  `data_expiracao` date NOT NULL,
  `estado` enum('Activo','Expirado','Arquivado') NOT NULL DEFAULT 'Activo',
  `criado_por` varchar(150) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `horario_ciclo`
--

INSERT INTO `horario_ciclo` (`id_ciclo`, `id_turma`, `numero`, `data_inicio`, `data_expiracao`, `estado`, `criado_por`, `criado_em`) VALUES
(1, 1, 1, '2026-10-13', '2026-10-13', 'Activo', 'Migração automática', '2026-10-12 22:46:58'),
(2, 2, 1, '2026-10-13', '2026-10-13', 'Activo', 'Miguel Nhane', '2026-10-13 21:06:48'),
(3, 4, 1, '2026-10-13', '2026-10-13', 'Activo', 'Miguel Nhane', '2026-10-13 21:07:09');

-- --------------------------------------------------------

--
-- Table structure for table `horario_historico`
--

CREATE TABLE `horario_historico` (
  `id_historico` int(11) NOT NULL,
  `id_ciclo` int(11) NOT NULL,
  `id_turma` int(11) NOT NULL,
  `id_modulo` int(11) NOT NULL,
  `id_formador` int(11) NOT NULL,
  `id_sala` int(11) NOT NULL,
  `dia_semana` varchar(20) NOT NULL,
  `turno` varchar(20) NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fim` time NOT NULL,
  `arquivado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `modulo`
--

CREATE TABLE `modulo` (
  `id_modulo` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `siglas` varchar(20) NOT NULL,
  `carga_horaria` int(11) NOT NULL DEFAULT 40,
  `ordem` int(11) NOT NULL DEFAULT 1,
  `tipo_sala` varchar(30) DEFAULT NULL,
  `id_curso` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `modulo`
--

INSERT INTO `modulo` (`id_modulo`, `nome`, `siglas`, `carga_horaria`, `ordem`, `tipo_sala`, `id_curso`) VALUES
(1, 'Utilizar bibliotecas em JavaScript', 'UBJ', 80, 1, 'Laboratorio', 1),
(2, 'Aplicar CSS em páginas web', 'APCSS', 80, 2, 'Laboratorio', 1),
(3, 'Aceder a dados e serviços em aplicações web com programação do lado do servidor', 'ADSAWPLS', 90, 3, 'Laboratorio', 1),
(4, 'Garantir a segurança de aplicações web', 'GSAW', 80, 4, 'Normal', 1),
(5, 'Elaborar um projecto de uma aplicação Web', 'EPI', 40, 5, 'Laboratorio', 1),
(6, 'Definir objectivos para a vida', 'DOV', 20, 1, 'Normal', 1),
(7, 'Adoptar hábitos de vida saudáveis', 'AHVS', 20, 2, 'Normal', 1),
(8, 'Usar o Inglês para propósitos sociais, pessoais e de negócios', 'UIPSPN', 10, 3, 'Normal', 1),
(9, 'Comunicar informação, em língua inglesa, relacionada com o trabalho', 'CILIRT', 10, 4, 'Normal', 1),
(10, 'Ler e responder a materiais escritos na língua inglesa', 'LRMELI', 10, 5, 'Normal', 1),
(11, 'Produzir materiais escritos na língua inglesa', 'PMELI', 10, 6, 'Normal', 1),
(12, 'Resolver problemas de crescimento logarítmico', 'RPCL', 20, 7, 'Normal', 1),
(13, 'Resolver problemas de optimização usando limites e derivadas', 'RPOLD', 20, 8, 'Normal', 1),
(14, 'Participar num debate como orador principal e como interveniente', 'PDOPI', 20, 9, 'Normal', 1),
(15, 'Interpretar informação contida em textos de carácter informativo e explicativo', 'IICTIE', 20, 10, 'Normal', 1),
(16, 'Conhecer os diferentes tipos de aplicações', 'CDTA', 40, 11, 'Normal', 1),
(17, 'Analisar e desenhar sistemas de informação', 'ADSI', 100, 12, 'Normal', 1),
(18, 'Programar em linguagem orientada a objectos', 'PLOO', 80, 13, 'Laboratorio', 1),
(19, 'Saber usar bibliotecas no desenvolvimento de software', 'SUBDS', 60, 14, 'Laboratorio', 1),
(20, 'Desenhar, construir, manipular e optimizar bases de dados', 'DCMOBD', 100, 15, 'Laboratorio', 1),
(21, 'Desenvolver páginas web em HTML', 'DPWH', 60, 16, 'Laboratorio', 1),
(22, 'Desenvolver aplicações web com JavaScript', 'DAWJS', 90, 18, 'Laboratorio', 1),
(23, 'Desenvolver páginas web dinâmicas com programação do lado do servidor', 'DPWDPLS', 100, 20, 'Laboratorio', 1);

-- --------------------------------------------------------

--
-- Table structure for table `sala`
--

CREATE TABLE `sala` (
  `id_sala` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `capacidade` int(11) NOT NULL,
  `tipo` varchar(30) NOT NULL DEFAULT 'Normal',
  `id_bloco` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sala`
--

INSERT INTO `sala` (`id_sala`, `nome`, `capacidade`, `tipo`, `id_bloco`) VALUES
(1, 'Sala A1', 35, 'Normal', 1),
(2, 'Lab. Informática 1', 30, 'Laboratorio', 1),
(3, 'Lab. Informática 2', 30, 'Laboratorio', 1),
(4, 'Lab. Informática 3', 30, 'Laboratorio', 1),
(5, 'Lab. Informática 4', 30, 'Laboratorio', 1),
(6, 'Lab. Eletricidade Residencial', 30, 'Laboratorio de Electricidade', 1),
(7, 'Lab. Eletricidade 2', 35, 'Laboratorio de Electricidade', 1),
(8, 'Lab. Eletricidade 3', 30, 'Laboratorio de Electricidade', 1),
(9, 'SalaA 2', 35, 'Normal', 1),
(10, 'Sala B1', 35, 'Normal', 2),
(11, 'Sala B2', 35, 'Normal', 2),
(12, 'Sala B3', 35, 'Normal', 2),
(13, 'Sala B4', 35, 'Normal', 2),
(14, 'Sala C1', 35, 'Normal', 3),
(15, 'Sala C2', 35, 'Normal', 3),
(16, 'Sala C3', 35, 'Normal', 3),
(17, 'Sala C4', 35, 'Normal', 3),
(18, 'Sala D1', 35, 'Normal', 6),
(19, 'Sala D2', 35, 'Normal', 6),
(20, 'Sala D3', 35, 'Normal', 6);

-- --------------------------------------------------------

--
-- Table structure for table `turma`
--

CREATE TABLE `turma` (
  `id_turma` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `id_curso` int(11) NOT NULL,
  `turno` enum('manha','tarde','noite') NOT NULL DEFAULT 'noite',
  `num_formandos` int(11) NOT NULL DEFAULT 30,
  `data_inicio` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `turma`
--

INSERT INTO `turma` (`id_turma`, `nome`, `id_curso`, `turno`, `num_formandos`, `data_inicio`) VALUES
(1, 'TPW2', 1, 'noite', 30, '2026-10-12'),
(2, 'TPW1', 1, 'manha', 30, '2026-10-12'),
(4, 'TPW3', 1, 'noite', 30, '2026-10-13'),
(5, 'TPW4', 1, 'noite', 10, '2026-03-05');

-- --------------------------------------------------------

--
-- Table structure for table `turma_modulo`
--

CREATE TABLE `turma_modulo` (
  `id_turma` int(11) NOT NULL,
  `id_modulo` int(11) NOT NULL,
  `id_formador` int(11) NOT NULL,
  `aulas_semana` tinyint(4) NOT NULL DEFAULT 1,
  `tempos_aula` tinyint(4) NOT NULL DEFAULT 5,
  `data_inicio` date DEFAULT NULL,
  `data_fim` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `turma_modulo`
--

INSERT INTO `turma_modulo` (`id_turma`, `id_modulo`, `id_formador`, `aulas_semana`, `tempos_aula`, `data_inicio`, `data_fim`) VALUES
(1, 1, 1, 2, 5, '2026-10-12', '2026-12-12'),
(1, 2, 2, 1, 5, '2026-09-23', '2026-11-04'),
(1, 3, 3, 1, 5, '2026-10-23', '2026-12-18'),
(1, 4, 2, 1, 5, '2026-10-10', '2027-01-17'),
(1, 5, 1, 1, 5, '2026-12-07', '2027-01-13');

-- --------------------------------------------------------

--
-- Table structure for table `utilizador`
--

CREATE TABLE `utilizador` (
  `id_utilizador` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(120) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `perfil` enum('Chefe de Departamento','Formador') NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `utilizador`
--

INSERT INTO `utilizador` (`id_utilizador`, `nome`, `email`, `senha`, `perfil`, `ativo`, `criado_em`) VALUES
(1, 'Administrador', 'admin@gestao.local', '$2y$12$G2V18IFVaA6pl1teQmUaquz4/ZkBNwIwZYKYNPvxsMzHU7oSUxD7i', 'Chefe de Departamento', 1, '2026-10-12 19:50:12'),
(3, 'Miguel Nhane', 'miguelnhane@gmail.com', '$2y$10$8Ju7TwyqXxkRXKowzRqRcexvkQya06SL4t4U/liQdWSWH3wwsIUG2', 'Chefe de Departamento', 1, '2026-10-12 21:34:35'),
(4, 'Gil Tembe JR', 'giltembe260@gmail.com', '$2y$10$n/g2P/e3IzhqMYeEpqj1keMNDeTZHgWneoIXgE5P95TQTsRJHmllO', 'Chefe de Departamento', 1, '2026-10-13 21:27:09'),
(5, 'Wanga Estevao', 'wwanga@gmail.com', '$2y$10$7O6xB9EwOH82K.rLIDa/OOw0UfrcBbp5a7RspBiA.KHQ8f3ZIk/2e', 'Formador', 1, '2026-10-13 21:28:38');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bloco_de_salas`
--
ALTER TABLE `bloco_de_salas`
  ADD PRIMARY KEY (`id_bloco`),
  ADD UNIQUE KEY `uq_bloco` (`nome`,`id_departamento`),
  ADD KEY `fk_bloco_departamento` (`id_departamento`);

--
-- Indexes for table `curso`
--
ALTER TABLE `curso`
  ADD PRIMARY KEY (`id_curso`),
  ADD UNIQUE KEY `uq_curso_departamento` (`nome`,`id_departamento`),
  ADD KEY `fk_curso_departamento` (`id_departamento`);

--
-- Indexes for table `departamento`
--
ALTER TABLE `departamento`
  ADD PRIMARY KEY (`id_departamento`),
  ADD UNIQUE KEY `nome` (`nome`);

--
-- Indexes for table `disponibilidade`
--
ALTER TABLE `disponibilidade`
  ADD PRIMARY KEY (`id_disponibilidade`),
  ADD UNIQUE KEY `uq_disp` (`id_formador`,`dia_semana`,`hora_inicio`,`hora_fim`);

--
-- Indexes for table `formador`
--
ALTER TABLE `formador`
  ADD PRIMARY KEY (`id_formador`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `for_mod`
--
ALTER TABLE `for_mod`
  ADD PRIMARY KEY (`id_formador`,`id_modulo`),
  ADD KEY `fk_formod_modulo` (`id_modulo`);

--
-- Indexes for table `horario`
--
ALTER TABLE `horario`
  ADD PRIMARY KEY (`id_horario`),
  ADD UNIQUE KEY `uq_horario` (`id_turma`,`dia_semana`,`hora_inicio`),
  ADD KEY `idx_formador_hora` (`id_formador`,`dia_semana`,`hora_inicio`),
  ADD KEY `idx_sala_hora` (`id_sala`,`dia_semana`,`hora_inicio`),
  ADD KEY `fk_horario_modulo` (`id_modulo`);

--
-- Indexes for table `horario_ciclo`
--
ALTER TABLE `horario_ciclo`
  ADD PRIMARY KEY (`id_ciclo`),
  ADD UNIQUE KEY `uq_turma_ciclo` (`id_turma`,`numero`);

--
-- Indexes for table `horario_historico`
--
ALTER TABLE `horario_historico`
  ADD PRIMARY KEY (`id_historico`),
  ADD KEY `id_turma` (`id_turma`,`id_modulo`);

--
-- Indexes for table `modulo`
--
ALTER TABLE `modulo`
  ADD PRIMARY KEY (`id_modulo`),
  ADD UNIQUE KEY `siglas` (`siglas`),
  ADD KEY `fk_modulo_curso` (`id_curso`);

--
-- Indexes for table `sala`
--
ALTER TABLE `sala`
  ADD PRIMARY KEY (`id_sala`),
  ADD UNIQUE KEY `uq_sala_bloco` (`nome`,`id_bloco`),
  ADD KEY `fk_sala_bloco` (`id_bloco`);

--
-- Indexes for table `turma`
--
ALTER TABLE `turma`
  ADD PRIMARY KEY (`id_turma`),
  ADD UNIQUE KEY `nome` (`nome`),
  ADD KEY `fk_turma_curso` (`id_curso`);

--
-- Indexes for table `turma_modulo`
--
ALTER TABLE `turma_modulo`
  ADD PRIMARY KEY (`id_turma`,`id_modulo`),
  ADD KEY `fk_tm_modulo` (`id_modulo`),
  ADD KEY `fk_tm_formador` (`id_formador`);

--
-- Indexes for table `utilizador`
--
ALTER TABLE `utilizador`
  ADD PRIMARY KEY (`id_utilizador`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bloco_de_salas`
--
ALTER TABLE `bloco_de_salas`
  MODIFY `id_bloco` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `curso`
--
ALTER TABLE `curso`
  MODIFY `id_curso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `departamento`
--
ALTER TABLE `departamento`
  MODIFY `id_departamento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `disponibilidade`
--
ALTER TABLE `disponibilidade`
  MODIFY `id_disponibilidade` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `formador`
--
ALTER TABLE `formador`
  MODIFY `id_formador` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `horario`
--
ALTER TABLE `horario`
  MODIFY `id_horario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `horario_ciclo`
--
ALTER TABLE `horario_ciclo`
  MODIFY `id_ciclo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `horario_historico`
--
ALTER TABLE `horario_historico`
  MODIFY `id_historico` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `modulo`
--
ALTER TABLE `modulo`
  MODIFY `id_modulo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `sala`
--
ALTER TABLE `sala`
  MODIFY `id_sala` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `turma`
--
ALTER TABLE `turma`
  MODIFY `id_turma` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `utilizador`
--
ALTER TABLE `utilizador`
  MODIFY `id_utilizador` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bloco_de_salas`
--
ALTER TABLE `bloco_de_salas`
  ADD CONSTRAINT `fk_bloco_departamento` FOREIGN KEY (`id_departamento`) REFERENCES `departamento` (`id_departamento`) ON UPDATE CASCADE;

--
-- Constraints for table `curso`
--
ALTER TABLE `curso`
  ADD CONSTRAINT `fk_curso_departamento` FOREIGN KEY (`id_departamento`) REFERENCES `departamento` (`id_departamento`) ON UPDATE CASCADE;

--
-- Constraints for table `disponibilidade`
--
ALTER TABLE `disponibilidade`
  ADD CONSTRAINT `fk_disp_formador` FOREIGN KEY (`id_formador`) REFERENCES `formador` (`id_formador`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `for_mod`
--
ALTER TABLE `for_mod`
  ADD CONSTRAINT `fk_formod_formador` FOREIGN KEY (`id_formador`) REFERENCES `formador` (`id_formador`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_formod_modulo` FOREIGN KEY (`id_modulo`) REFERENCES `modulo` (`id_modulo`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `horario`
--
ALTER TABLE `horario`
  ADD CONSTRAINT `fk_horario_formador` FOREIGN KEY (`id_formador`) REFERENCES `formador` (`id_formador`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_horario_modulo` FOREIGN KEY (`id_modulo`) REFERENCES `modulo` (`id_modulo`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_horario_sala` FOREIGN KEY (`id_sala`) REFERENCES `sala` (`id_sala`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_horario_turma` FOREIGN KEY (`id_turma`) REFERENCES `turma` (`id_turma`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `modulo`
--
ALTER TABLE `modulo`
  ADD CONSTRAINT `fk_modulo_curso` FOREIGN KEY (`id_curso`) REFERENCES `curso` (`id_curso`) ON UPDATE CASCADE;

--
-- Constraints for table `sala`
--
ALTER TABLE `sala`
  ADD CONSTRAINT `fk_sala_bloco` FOREIGN KEY (`id_bloco`) REFERENCES `bloco_de_salas` (`id_bloco`) ON UPDATE CASCADE;

--
-- Constraints for table `turma`
--
ALTER TABLE `turma`
  ADD CONSTRAINT `fk_turma_curso` FOREIGN KEY (`id_curso`) REFERENCES `curso` (`id_curso`) ON UPDATE CASCADE;

--
-- Constraints for table `turma_modulo`
--
ALTER TABLE `turma_modulo`
  ADD CONSTRAINT `fk_tm_formador` FOREIGN KEY (`id_formador`) REFERENCES `formador` (`id_formador`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tm_modulo` FOREIGN KEY (`id_modulo`) REFERENCES `modulo` (`id_modulo`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tm_turma` FOREIGN KEY (`id_turma`) REFERENCES `turma` (`id_turma`) ON DELETE CASCADE ON UPDATE CASCADE;

-- Tabelas do Chat Box integradas ao dump enviado
-- Chat Box do SGH: executar na base de dados gestao_horarios existente.
-- Faça uma cópia de segurança antes de executar.
CREATE TABLE IF NOT EXISTS chat_conversa (
  id_conversa INT NOT NULL AUTO_INCREMENT,
  id_formador INT NOT NULL,
  id_administrador INT NOT NULL,
  criada_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  estado ENUM('Aberta','Fechada') NOT NULL DEFAULT 'Aberta',
  PRIMARY KEY (id_conversa),
  UNIQUE KEY uq_chat_formador (id_formador),
  KEY idx_chat_admin (id_administrador)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS chat_mensagem (
  id_mensagem INT NOT NULL AUTO_INCREMENT,
  id_conversa INT NOT NULL,
  id_remetente INT NOT NULL,
  id_horario INT NULL,
  mensagem TEXT NOT NULL,
  enviada_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  lida_em DATETIME NULL,
  PRIMARY KEY (id_mensagem),
  KEY idx_mensagem_conversa_data (id_conversa, enviada_em),
  KEY idx_mensagem_remetente (id_remetente),
  KEY idx_mensagem_horario (id_horario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
