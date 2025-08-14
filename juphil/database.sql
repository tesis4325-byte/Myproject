CREATE DATABASE IF NOT EXISTS `love_journal` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `love_journal`;

CREATE TABLE `THEMES` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `css_path` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `THEMES` (`id`, `name`, `css_path`) VALUES
(1, 'Sakura', 'css/sakura.css'),
(2, 'Minimalist White', 'css/minimalist-white.css'),
(3, 'Blush Pink', 'css/blush-pink.css');

CREATE TABLE `COUPLES` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `theme_id` int(11) NOT NULL DEFAULT 1,
  `invite_code` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `theme_id` (`theme_id`),
  CONSTRAINT `couples_ibfk_1` FOREIGN KEY (`theme_id`) REFERENCES `THEMES` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `USERS` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `couple_id` int(11) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `couple_id` (`couple_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`couple_id`) REFERENCES `COUPLES` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `ENTRIES` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `couple_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `entry_date` date NOT NULL,
  `is_private` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `couple_id` (`couple_id`),
  KEY `author_id` (`author_id`),
  CONSTRAINT `entries_ibfk_1` FOREIGN KEY (`couple_id`) REFERENCES `COUPLES` (`id`),
  CONSTRAINT `entries_ibfk_2` FOREIGN KEY (`author_id`) REFERENCES `USERS` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `PHOTOS` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entry_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `caption` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `entry_id` (`entry_id`),
  CONSTRAINT `photos_ibfk_1` FOREIGN KEY (`entry_id`) REFERENCES `ENTRIES` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `MOODS` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `emoji` varchar(10) NOT NULL,
  `mood_date` date NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `moods_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `USERS` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;