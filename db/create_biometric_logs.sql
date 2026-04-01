-- Create biometric_logs table if it doesn't exist
-- Run this in your testdb database

DROP TABLE IF EXISTS `biometric_logs`;
CREATE TABLE IF NOT EXISTS `biometric_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `user_type` enum('student','staff','tutor') NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `fingerprint_match_score` decimal(5,2) DEFAULT NULL,
  `success` tinyint(1) NOT NULL DEFAULT '0',
  `ip_address` varchar(45) DEFAULT NULL,
  `device_info` text,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`,`user_type`),
  KEY `idx_timestamp` (`timestamp` DESC),
  KEY `idx_action` (`action_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

