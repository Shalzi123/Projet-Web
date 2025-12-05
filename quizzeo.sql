-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : ven. 05 déc. 2025 à 00:17
-- Version du serveur : 9.1.0
-- Version de PHP : 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `quizzeo`
--

-- --------------------------------------------------------

--
-- Structure de la table `group_invitations`
--

DROP TABLE IF EXISTS `group_invitations`;
CREATE TABLE IF NOT EXISTS `group_invitations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `group_id` int NOT NULL,
  `token` varchar(64) NOT NULL,
  `created_by` int NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `group_id` (`group_id`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `group_invitations`
--

INSERT INTO `group_invitations` (`id`, `group_id`, `token`, `created_by`, `expires_at`, `created_at`) VALUES
(6, 22, '94fde4eee00f4417264ccdee313b26f5', 26, '2025-12-11 22:52:48', '2025-12-04 22:52:48');

-- --------------------------------------------------------

--
-- Structure de la table `sql_groups`
--

DROP TABLE IF EXISTS `sql_groups`;
CREATE TABLE IF NOT EXISTS `sql_groups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_administrateurs` varchar(255) NOT NULL,
  `id_visiteurs` varchar(255) NOT NULL,
  `nomgroupe` varchar(30) NOT NULL,
  `descriptiongroupe` varchar(120) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `sql_groups`
--

INSERT INTO `sql_groups` (`id`, `id_administrateurs`, `id_visiteurs`, `nomgroupe`, `descriptiongroupe`) VALUES
(22, '', '', 'Groupe 1', 'premier groupe');

-- --------------------------------------------------------

--
-- Structure de la table `sql_questions`
--

DROP TABLE IF EXISTS `sql_questions`;
CREATE TABLE IF NOT EXISTS `sql_questions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `quizz_id` int NOT NULL,
  `type` varchar(255) NOT NULL,
  `question` varchar(300) NOT NULL,
  `options` varchar(255) NOT NULL,
  `reponse` varchar(300) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `sql_questions`
--

INSERT INTO `sql_questions` (`id`, `quizz_id`, `type`, `question`, `options`, `reponse`) VALUES
(8, 8, 'single', 'Quel est le meilleur jeu ?', '[\"Fifa\",\"Outer Wilds\",\"Roblox\"]', '[1]'),
(9, 8, 'single', 'Comment s\'appelle le célèbre plombier rouge ?', '[\"G\\u00e9rome\",\"Spaghettis bolognaise carbonara pizza\",\"Mario\",\"Augustin\"]', '[2]'),
(10, 8, 'multiple', 'Quels personnages existent dans COD Black Ops 2 ?', '[\"Riz-au-lait (tr\\u00e8s m\\u00e9chant)\",\"David Mason\",\"Raoul Menendez\",\"Jim petits pieds\",\"Olivier Amar\"]', '[1,2]'),
(11, 8, 'single', 'Bonus : Qui est le plus cool ?', '[\"Romain CROS\",\"Sacha RIBOLZI\",\"Olivier AMAR\",\"Lo\\u00efs LEBLOND\",\"Samuel Processeur\"]', '[4]'),
(12, 9, 'single', 'Qui a démarré la seconde guerre mondiale ?', '[\"Passe-partout\",\"Dark Vador\",\"Donatien\",\"Un Fiat Multipla\"]', '[2]'),
(13, 9, 'single', 'Comment régler la famine dans le monde ?', '[\"Un poulet bascaise\",\"Un sketch de Kev Adams\",\"L\'argent stratosph\\u00e9rique d\'Olivier Amar\",\"Des chocolats kinder\"]', '[2]'),
(14, 9, 'multiple', 'Les phrases les plus connues de Paul Mirabelle ?', '[\"\\\"Un donut sucr\\u00e9 au sucre\\\"\",\"\\\"Salam les rohyas\\\"\",\"\\\"C\'est ciao\\\"\",\"\\\"Ils ont fum\\u00e9 les devs ou quoi !??\\\"\",\"\\\"Du coup je n\'ai plus de porte-feuille\\\"\"]', '[1,4]'),
(15, 9, 'single', 'Quel est le meilleur agent secret ?', '[\"Perry l\'ornithorynque\",\"R\\u00e9mi\",\"Gustave\",\"Lilian\"]', '[0]');

-- --------------------------------------------------------

--
-- Structure de la table `sql_quizz`
--

DROP TABLE IF EXISTS `sql_quizz`;
CREATE TABLE IF NOT EXISTS `sql_quizz` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(22) NOT NULL,
  `theme` varchar(40) NOT NULL,
  `description` varchar(50) NOT NULL,
  `etatquizz` varchar(20) NOT NULL,
  `nbr_question` int NOT NULL,
  `question_id` int NOT NULL,
  `group_id` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `sql_quizz`
--

INSERT INTO `sql_quizz` (`id`, `nom`, `theme`, `description`, `etatquizz`, `nbr_question`, `question_id`, `group_id`) VALUES
(8, 'Culture JV', '🎮', 'Testez votre culture JV', 'actif', 4, 0, 22),
(9, 'Culture G', '🌏', 'Testez votre culture G', 'actif', 4, 0, 22);

-- --------------------------------------------------------

--
-- Structure de la table `sql_reponse_utilisateur`
--

DROP TABLE IF EXISTS `sql_reponse_utilisateur`;
CREATE TABLE IF NOT EXISTS `sql_reponse_utilisateur` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int NOT NULL,
  `id_question` int NOT NULL,
  `reponse_utilisateur` varchar(300) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `sql_utilisateur`
--

DROP TABLE IF EXISTS `sql_utilisateur`;
CREATE TABLE IF NOT EXISTS `sql_utilisateur` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(22) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `password` varchar(60) NOT NULL,
  `role` varchar(22) NOT NULL DEFAULT 'utilisateur',
  `banned` tinyint(1) NOT NULL DEFAULT '0',
  `remember_token` varchar(255) DEFAULT NULL,
  `id_groups` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `sql_utilisateur`
--

INSERT INTO `sql_utilisateur` (`id`, `username`, `password`, `role`, `banned`, `remember_token`, `id_groups`) VALUES
(12, 'sigmaphonk', '$2y$10$LvmU6UA6urxMpMuBKFUhieAgnkYY7xgh66XNwsZOPtKwwVgYOs/YO', 'utilisateur', 1, NULL, 0),
(24, 'John Pork', '$2y$10$6T2Sq3wZjIByHabLTcuMEex1m0TQ35OxKsHQ0EyBQF2Cfr4remNAS', 'utilisateur', 0, NULL, 0),
(25, 'admin', '$2y$10$1WotN.Q4p9.l0M5hK.x7r.x9BVTqdiR9gqIYrCWsx7i2Hpf1B6rW2', 'admin', 0, NULL, 0),
(26, 'Angoulime', '$2y$10$X.9llT1KKSvMY3MbCXVDKOsMaUreWyWHQVaNRCeNNgoHi7kAqBXGi', 'entreprise', 0, NULL, 0);

-- --------------------------------------------------------

--
-- Structure de la table `utilisateur_groups`
--

DROP TABLE IF EXISTS `utilisateur_groups`;
CREATE TABLE IF NOT EXISTS `utilisateur_groups` (
  `user_id` int NOT NULL,
  `group_id` int NOT NULL,
  `role` varchar(255) NOT NULL,
  PRIMARY KEY (`user_id`,`group_id`),
  KEY `group_id` (`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `utilisateur_groups`
--

INSERT INTO `utilisateur_groups` (`user_id`, `group_id`, `role`) VALUES
(24, 22, 'member'),
(26, 22, '');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
