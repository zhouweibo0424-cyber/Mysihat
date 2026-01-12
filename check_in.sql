CREATE TABLE daily_checkins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    checkin_date DATE NOT NULL,
    points_earned INT DEFAULT 10,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_date (user_id, checkin_date)
);

ALTER TABLE users ADD COLUMN checkin_streak INT DEFAULT 0;
ALTER TABLE users ADD COLUMN last_checkin_date DATE DEFAULT NULL;