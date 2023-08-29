-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Aug 29, 2023 at 07:10 AM
-- Server version: 10.5.16-MariaDB
-- PHP Version: 8.2.9
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */
;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */
;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */
;
/*!40101 SET NAMES utf8mb4 */
;
--
-- Database: `chatflow`
--

-- --------------------------------------------------------
--
-- Table structure for table `api_tokens`
--

CREATE TABLE `api_tokens` (
    `token_id` bigint(20) NOT NULL,
    `token` varchar(255) NOT NULL,
    `user_id` bigint(20) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- --------------------------------------------------------
--
-- Table structure for table `chatgpt_api_keys`
--

CREATE TABLE `chatgpt_api_keys` (
    `key_id` bigint(20) NOT NULL,
    `user_id` bigint(20) NOT NULL,
    `key` varchar(255) NOT NULL,
    `is_default` tinyint(1) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- --------------------------------------------------------
--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
    `message_id` bigint(20) NOT NULL,
    `session_id` bigint(20) NOT NULL,
    `role` enum('system', 'user', 'assistant', 'function') NOT NULL,
    `content` text NOT NULL,
    `token_count` bigint(20) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- --------------------------------------------------------
--
-- Table structure for table `collections`
--

CREATE TABLE `collections` (
    `collection_id` bigint(20) NOT NULL,
    `collection_name` varchar(255) NOT NULL,
    `user_id` bigint(20) NOT NULL,
    `chatgpt_key_id` bigint(20) NOT NULL,
    `api_token_id` bigint(20) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- --------------------------------------------------------
--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
    `session_id` bigint(20) NOT NULL,
    `collection_id` bigint(20) NOT NULL,
    `session_data` varchar(255) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- --------------------------------------------------------
--
-- Table structure for table `users`
--

CREATE TABLE `users` (
    `user_id` bigint(20) NOT NULL,
    `username` varchar(255) NOT NULL,
    `email` varchar(255) NOT NULL,
    `password` varchar(64) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `last_login_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
--
-- Indexes for dumped tables
--

--
-- Indexes for table `api_tokens`
--
ALTER TABLE `api_tokens`
ADD PRIMARY KEY (`token_id`),
    ADD KEY `user_id` (`user_id`);
--
-- Indexes for table `chatgpt_api_keys`
--
ALTER TABLE `chatgpt_api_keys`
ADD PRIMARY KEY (`key_id`),
    ADD KEY `user_id` (`user_id`);
--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
ADD PRIMARY KEY (`message_id`),
    ADD KEY `session_id` (`session_id`);
--
-- Indexes for table `collections`
--
ALTER TABLE `collections`
ADD PRIMARY KEY (`collection_id`),
    ADD KEY `user_id` (`user_id`),
    ADD KEY `chatgpt_key_id` (`chatgpt_key_id`),
    ADD KEY `api_token_id` (`api_token_id`);
--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
ADD PRIMARY KEY (`session_id`),
    ADD KEY `collection_id` (`collection_id`);
--
-- Indexes for table `users`
--
ALTER TABLE `users`
ADD PRIMARY KEY (`user_id`);
--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `api_tokens`
--
ALTER TABLE `api_tokens`
MODIFY `token_id` bigint(20) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `chatgpt_api_keys`
--
ALTER TABLE `chatgpt_api_keys`
MODIFY `key_id` bigint(20) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
MODIFY `message_id` bigint(20) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `collections`
--
ALTER TABLE `collections`
MODIFY `collection_id` bigint(20) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
MODIFY `session_id` bigint(20) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
MODIFY `user_id` bigint(20) NOT NULL AUTO_INCREMENT;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `api_tokens`
--
ALTER TABLE `api_tokens`
ADD CONSTRAINT `api_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
--
-- Constraints for table `chatgpt_api_keys`
--
ALTER TABLE `chatgpt_api_keys`
ADD CONSTRAINT `chatgpt_api_keys_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`session_id`) ON DELETE CASCADE ON UPDATE CASCADE;
--
-- Constraints for table `collections`
--
ALTER TABLE `collections`
ADD CONSTRAINT `collections_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `collections_ibfk_2` FOREIGN KEY (`chatgpt_key_id`) REFERENCES `chatgpt_api_keys` (`key_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `collections_ibfk_3` FOREIGN KEY (`api_token_id`) REFERENCES `api_tokens` (`token_id`) ON DELETE CASCADE ON UPDATE CASCADE;
--
-- Constraints for table `sessions`
--
ALTER TABLE `sessions`
ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`collection_id`) REFERENCES `collections` (`collection_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */
;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */
;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */
;