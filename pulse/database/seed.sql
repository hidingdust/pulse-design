SET NAMES utf8mb4;
SET time_zone = '+08:00';

USE pulse;

INSERT INTO metric_samples (
    cpu_load, memory_used_pct, disk_used_pct, online_count, mood, energy, trust, source, created_at
) VALUES
    (0.42, 41.20, 28.00, 1, 74, 70, 55, 'demo', NOW() - INTERVAL 55 MINUTE),
    (0.55, 42.40, 28.10, 2, 73, 69, 56, 'demo', NOW() - INTERVAL 50 MINUTE),
    (0.61, 43.00, 28.10, 2, 72, 68, 57, 'demo', NOW() - INTERVAL 45 MINUTE),
    (0.74, 44.80, 28.20, 3, 70, 66, 57, 'demo', NOW() - INTERVAL 40 MINUTE),
    (0.68, 45.20, 28.20, 3, 71, 67, 58, 'demo', NOW() - INTERVAL 35 MINUTE),
    (1.20, 50.10, 28.30, 5, 67, 62, 58, 'demo', NOW() - INTERVAL 30 MINUTE),
    (1.84, 56.70, 28.30, 7, 63, 57, 59, 'demo', NOW() - INTERVAL 25 MINUTE),
    (1.35, 52.20, 28.40, 5, 66, 60, 59, 'demo', NOW() - INTERVAL 20 MINUTE),
    (0.92, 48.60, 28.40, 4, 69, 64, 60, 'demo', NOW() - INTERVAL 15 MINUTE),
    (0.71, 46.10, 28.50, 3, 71, 66, 60, 'demo', NOW() - INTERVAL 10 MINUTE),
    (0.63, 44.90, 28.50, 2, 72, 68, 61, 'demo', NOW() - INTERVAL 5 MINUTE),
    (0.58, 44.20, 28.50, 1, 73, 69, 61, 'demo', NOW());

INSERT INTO events (type, message, severity, metadata) VALUES
    ('system', 'PULSE 在云服务器上醒来了。', 'info', JSON_OBJECT('seed', TRUE)),
    ('system', '它正在适应这台服务器的呼吸节奏。', 'info', JSON_OBJECT('seed', TRUE));

