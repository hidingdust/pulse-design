SET NAMES utf8mb4;
SET time_zone = '+08:00';

CREATE DATABASE IF NOT EXISTS pulse
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE pulse;

CREATE TABLE IF NOT EXISTS pulse_state (
    id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
    mood TINYINT UNSIGNED NOT NULL DEFAULT 72,
    energy TINYINT UNSIGNED NOT NULL DEFAULT 68,
    trust TINYINT UNSIGNED NOT NULL DEFAULT 55,
    status VARCHAR(24) NOT NULL DEFAULT 'calm',
    stage VARCHAR(24) NOT NULL DEFAULT 'sprout',
    last_interaction_at DATETIME NULL,
    last_visit_at DATETIME NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_single_pulse CHECK (id = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO pulse_state (
    id, mood, energy, trust, status, stage, last_interaction_at, last_visit_at
) VALUES (
    1, 72, 68, 55, 'calm', 'sprout', NOW(), NOW()
)
ON DUPLICATE KEY UPDATE
    mood = VALUES(mood),
    energy = VALUES(energy),
    trust = VALUES(trust),
    status = VALUES(status),
    stage = VALUES(stage);

CREATE TABLE IF NOT EXISTS metric_samples (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    cpu_load DECIMAL(6,2) NOT NULL DEFAULT 0,
    memory_used_pct DECIMAL(5,2) NOT NULL DEFAULT 0,
    disk_used_pct DECIMAL(5,2) NOT NULL DEFAULT 0,
    online_count INT UNSIGNED NOT NULL DEFAULT 0,
    mood TINYINT UNSIGNED NOT NULL DEFAULT 72,
    energy TINYINT UNSIGNED NOT NULL DEFAULT 68,
    trust TINYINT UNSIGNED NOT NULL DEFAULT 55,
    source VARCHAR(12) NOT NULL DEFAULT 'real',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_metric_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS visitors (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    session_key CHAR(64) NOT NULL UNIQUE,
    first_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    interaction_count INT UNSIGNED NOT NULL DEFAULT 0,
    INDEX idx_visitor_last_seen (last_seen_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS interactions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    visitor_id BIGINT UNSIGNED NOT NULL,
    action VARCHAR(24) NOT NULL,
    before_state JSON NULL,
    after_state JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_interaction_visitor_time (visitor_id, created_at),
    INDEX idx_interaction_created_at (created_at),
    CONSTRAINT fk_interaction_visitor
        FOREIGN KEY (visitor_id) REFERENCES visitors(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(32) NOT NULL,
    message VARCHAR(255) NOT NULL,
    severity VARCHAR(12) NOT NULL DEFAULT 'info',
    metadata JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_created_at (created_at),
    INDEX idx_event_type_created (type, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

