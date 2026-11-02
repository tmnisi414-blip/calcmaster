-- ============================================================
--  CalcMaster Database Setup
--  Run this in phpMyAdmin or MySQL CLI before launching the app
-- ============================================================

CREATE DATABASE IF NOT EXISTS `calcmaster`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `calcmaster`;

-- ── Users ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
  `id`          INT(11)      NOT NULL AUTO_INCREMENT,
  `username`    VARCHAR(50)  NOT NULL UNIQUE,
  `email`       VARCHAR(150) NOT NULL UNIQUE,
  `password`    VARCHAR(255) NOT NULL,
  `theme`       VARCHAR(20)  NOT NULL DEFAULT 'dark',
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Calculation History ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS `calculations` (
  `id`           INT(11)       NOT NULL AUTO_INCREMENT,
  `user_id`      INT(11)       NOT NULL,
  `expression`   VARCHAR(500)  NOT NULL,
  `result`       VARCHAR(255)  NOT NULL,
  `op_type`      VARCHAR(50)   NOT NULL DEFAULT 'arithmetic',
  `is_favourite` TINYINT(1)    NOT NULL DEFAULT 0,
  `created_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Sample admin user (password: Admin@123) ──────────────────
INSERT IGNORE INTO `users` (`username`, `email`, `password`) VALUES
  ('admin', 'admin@calcmaster.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uHxL.JiCy');
