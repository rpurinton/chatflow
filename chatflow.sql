CREATE TABLE `api_tokens` (
    `token_id` bigint(20) NOT NULL,
    `token` varchar(255) NOT NULL,
    `user_id` bigint(20) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
--
-- Dumping data for table `api_tokens`
--

INSERT INTO `api_tokens` (`token_id`, `token`, `user_id`, `created_at`)
VALUES (
        1143598434025218079,
        'chatflow-34f307b1c3a7e3c2b49f13b2f6974424566a5c20ffc97153899ab16',
        363853952749404162,
        '2023-08-29 08:01:41'
    );
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
--
-- Dumping data for table `chatgpt_api_keys`
--

INSERT INTO `chatgpt_api_keys` (
        `key_id`,
        `user_id`,
        `key`,
        `is_default`,
        `created_at`
    )
VALUES (
        1145688810265526404,
        363853952749404162,
        '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',
        1,
        '2023-08-29 07:50:47'
    );
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
--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (
        `message_id`,
        `session_id`,
        `role`,
        `content`,
        `token_count`,
        `created_at`
    )
VALUES (
        1144646985324969994,
        1143460184354717826,
        'user',
        'What is the capital of France?',
        7,
        '2023-08-29 08:10:59'
    );
-- --------------------------------------------------------
--
-- Table structure for table `collections`
--

CREATE TABLE `collections` (
    `collection_id` bigint(20) NOT NULL,
    `collection_name` varchar(255) NOT NULL,
    `user_id` bigint(20) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
--
-- Dumping data for table `collections`
--

INSERT INTO `collections` (
        `collection_id`,
        `collection_name`,
        `user_id`,
        `created_at`
    )
VALUES (
        1145714984433746051,
        'My Collection',
        363853952749404162,
        '2023-08-29 07:51:44'
    );
-- --------------------------------------------------------
--
-- Table structure for table `collections_api_tokens`
--

CREATE TABLE `collections_api_tokens` (
    `collection_id` bigint(20) DEFAULT NULL,
    `token_id` bigint(20) DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
--
-- Dumping data for table `collections_api_tokens`
--

INSERT INTO `collections_api_tokens` (`collection_id`, `token_id`)
VALUES (1145714984433746051, 1143598434025218079);
-- --------------------------------------------------------
--
-- Table structure for table `collections_chatgpt_keys`
--

CREATE TABLE `collections_chatgpt_keys` (
    `collection_id` bigint(20) DEFAULT NULL,
    `key_id` bigint(20) DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
--
-- Dumping data for table `collections_chatgpt_keys`
--

INSERT INTO `collections_chatgpt_keys` (`collection_id`, `key_id`)
VALUES (1145714984433746051, 1145688810265526404);
-- --------------------------------------------------------
--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
    `session_id` bigint(20) NOT NULL,
    `collection_id` bigint(20) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`session_id`, `collection_id`, `created_at`)
VALUES (
        1143460184354717826,
        1145714984433746051,
        '2023-08-29 08:09:05'
    );
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
-- Dumping data for table `users`
--

INSERT INTO `users` (
        `user_id`,
        `username`,
        `email`,
        `password`,
        `created_at`,
        `last_login_at`
    )
VALUES (
        363853952749404162,
        'rpurinton',
        'russell.purinton@gmail.com',
        'b2b9a996521cc0f96ee9cca7f9d2d08b89f0e497b7f7e57dfc844173a95a0b21',
        '2023-08-29 07:41:45',
        '2023-08-29 07:41:45'
    );
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
    ADD KEY `user_id` (`user_id`);
--
-- Indexes for table `collections_api_tokens`
--
ALTER TABLE `collections_api_tokens`
ADD KEY `collection_id` (`collection_id`),
    ADD KEY `token_id` (`token_id`);
--
-- Indexes for table `collections_chatgpt_keys`
--
ALTER TABLE `collections_chatgpt_keys`
ADD KEY `collection_id` (`collection_id`),
    ADD KEY `key_id` (`key_id`);
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
MODIFY `token_id` bigint(20) NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 1143598434025218080;
--
-- AUTO_INCREMENT for table `chatgpt_api_keys`
--
ALTER TABLE `chatgpt_api_keys`
MODIFY `key_id` bigint(20) NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 1145688810265526405;
--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
MODIFY `message_id` bigint(20) NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 1144646985324969995;
--
-- AUTO_INCREMENT for table `collections`
--
ALTER TABLE `collections`
MODIFY `collection_id` bigint(20) NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 1145714984433746052;
--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
MODIFY `session_id` bigint(20) NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 1143460184354717827;
--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
MODIFY `user_id` bigint(20) NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 363853952749404163;
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
ADD CONSTRAINT `collections_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
--
-- Constraints for table `collections_api_tokens`
--
ALTER TABLE `collections_api_tokens`
ADD CONSTRAINT `collections_api_tokens_ibfk_1` FOREIGN KEY (`collection_id`) REFERENCES `collections` (`collection_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `collections_api_tokens_ibfk_2` FOREIGN KEY (`token_id`) REFERENCES `api_tokens` (`token_id`) ON DELETE CASCADE ON UPDATE CASCADE;
--
-- Constraints for table `collections_chatgpt_keys`
--
ALTER TABLE `collections_chatgpt_keys`
ADD CONSTRAINT `collections_chatgpt_keys_ibfk_1` FOREIGN KEY (`collection_id`) REFERENCES `collections` (`collection_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `collections_chatgpt_keys_ibfk_2` FOREIGN KEY (`key_id`) REFERENCES `chatgpt_api_keys` (`key_id`) ON DELETE CASCADE ON UPDATE CASCADE;
--
-- Constraints for table `sessions`
--
ALTER TABLE `sessions`
ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`collection_id`) REFERENCES `collections` (`collection_id`) ON DELETE CASCADE ON UPDATE CASCADE;