-- Create achievements definition table
CREATE TABLE achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NOT NULL,
    icon VARCHAR(50) DEFAULT 'trophy',
    color_class VARCHAR(30) DEFAULT 'text-warning',
    required_points INT DEFAULT 0,
    required_steps BIGINT DEFAULT 0,
    sort_order INT DEFAULT 100
);

-- Create user achievements unlock record table
CREATE TABLE user_achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    achievement_id INT NOT NULL,
    unlocked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_ach (user_id, achievement_id)
);

-- Insert initial achievements (feel free to add more!)
INSERT INTO achievements (code, name, description, icon, color_class, required_points, required_steps, sort_order) VALUES
('starter', 'Healthy Starter', 'Earn a total of 100 points to begin your healthy journey', 'award', 'text-success', 100, 0, 10),
('walker_10k', '10K Step Master', 'Walk a total of 10,000 steps', 'footprints', 'text-info', 0, 10000, 20),
('point_collector', 'Point Collector', 'Earn a total of 300 points', 'gem', 'text-primary', 300, 0, 30),
('consistent', 'Consistency Star', 'Generate points for 7 consecutive days', 'star-fill', 'text-warning', 0, 0, 40),
('elite', 'Elite Walker', 'Earn a total of 600 points and reach Pro level', 'trophy-fill', 'text-danger', 600, 0, 50),
('marathon', 'Marathon Spirit', 'Walk a total of 50,000 steps', 'flag-fill', 'text-dark', 0, 50000, 60);