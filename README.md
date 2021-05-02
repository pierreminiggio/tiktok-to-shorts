# tiktok-to-shorts

Migration :

```sql
-- phpMyAdmin SQL Dump
-- version 4.7.0
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le :  Dim 02 mai 2021 à 00:46
-- Version du serveur :  5.7.17
-- Version de PHP :  5.6.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données :  `channel-storage`
--

-- --------------------------------------------------------

--
-- Structure de la table `shorts_channel`
--

CREATE TABLE `shorts_channel` (
  `id` int(11) NOT NULL,
  `youtube_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `shorts_channel_tiktok_account`
--

CREATE TABLE `shorts_channel_tiktok_account` (
  `id` int(11) NOT NULL,
  `shorts_id` int(11) NOT NULL,
  `tiktok_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `shorts_video`
--

CREATE TABLE `shorts_video` (
  `id` int(11) NOT NULL,
  `channel_id` int(11) NOT NULL,
  `shorts_id` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `shorts_video_tiktok_video`
--

CREATE TABLE `shorts_video_tiktok_video` (
  `id` int(11) NOT NULL,
  `shorts_id` int(11) NOT NULL,
  `tiktok_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `shorts_channel`
--
ALTER TABLE `shorts_channel`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `shorts_channel_tiktok_account`
--
ALTER TABLE `shorts_channel_tiktok_account`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `shorts_video`
--
ALTER TABLE `shorts_video`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `shorts_video_tiktok_video`
--
ALTER TABLE `shorts_video_tiktok_video`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `shorts_channel`
--
ALTER TABLE `shorts_channel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `shorts_channel_tiktok_account`
--
ALTER TABLE `shorts_channel_tiktok_account`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `shorts_video`
--
ALTER TABLE `shorts_video`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `shorts_video_tiktok_video`
--
ALTER TABLE `shorts_video_tiktok_video`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
  
ALTER TABLE `shorts_channel` ADD `description` TEXT NOT NULL AFTER `youtube_id`;

ALTER TABLE `shorts_channel`  ADD `heropost_login` VARCHAR(255) NOT NULL  AFTER `youtube_id`,  ADD `heropost_password` VARCHAR(255) NOT NULL  AFTER `heropost_login`,  ADD `google_client_id` TEXT NOT NULL  AFTER `heropost_password`,  ADD `google_client_secret` TEXT NOT NULL  AFTER `google_client_id`,  ADD `google_refresh_token` TEXT NOT NULL  AFTER `google_client_secret`;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
```
