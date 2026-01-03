-- MySihat AI (Web version) FULL database setup
-- Combined from:
--   1) database_setup.sql (core app + points + courses + achievements)
--   2) healthy.sql (health module tables + seed data)
--
-- Generated on 2026-01-03 (Asia/Kuala_Lumpur)

-- MySihat AI (Web version) database setup
CREATE DATABASE IF NOT EXISTS mysihat
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE mysihat;


-- Clean re-runnable setup: drop existing tables first
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS `achievements`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `contraindications`;
DROP TABLE IF EXISTS `courses`;
DROP TABLE IF EXISTS `daily_checkins`;
DROP TABLE IF EXISTS `daily_steps`;
DROP TABLE IF EXISTS `favorites`;
DROP TABLE IF EXISTS `health_alerts`;
DROP TABLE IF EXISTS `lessons`;
DROP TABLE IF EXISTS `meals`;
DROP TABLE IF EXISTS `medication_catalog`;
DROP TABLE IF EXISTS `menstrual_cycles`;
DROP TABLE IF EXISTS `points_ledger`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `rewards`;
DROP TABLE IF EXISTS `symptom_catalog`;
DROP TABLE IF EXISTS `symptom_rules`;
DROP TABLE IF EXISTS `user_achievements`;
DROP TABLE IF EXISTS `user_allergies`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS daily_steps (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  step_date DATE NOT NULL,
  steps INT NOT NULL DEFAULT 0,
  UNIQUE KEY uniq_user_date (user_id, step_date),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS meals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  meal_time DATETIME NOT NULL,
  meal_text VARCHAR(255) NOT NULL,
  estimated_kcal INT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS points_ledger (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  point_date DATE NOT NULL,
  points_earned INT NOT NULL DEFAULT 0,
  points_spent INT NOT NULL DEFAULT 0,
  reason VARCHAR(120) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =========================================================
-- Health Module (from healthy.sql)
-- Tables: symptom_catalog, medication_catalog, contraindications,
--         symptom_rules, health_alerts, user_allergies, menstrual_cycles
-- =========================================================

CREATE TABLE IF NOT EXISTS symptom_catalog (
  id INT AUTO_INCREMENT PRIMARY KEY,
  symptom_key VARCHAR(50) NOT NULL UNIQUE,
  symptom_name VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS medication_catalog (
  id INT AUTO_INCREMENT PRIMARY KEY,
  med_key VARCHAR(50) NOT NULL UNIQUE,
  med_name VARCHAR(100) NOT NULL,
  med_type VARCHAR(30) DEFAULT 'OTC',
  notes VARCHAR(255) DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS contraindications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  allergy_key VARCHAR(80) NOT NULL,
  med_key VARCHAR(50) NOT NULL,
  note VARCHAR(255) DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS symptom_rules (
  id INT AUTO_INCREMENT PRIMARY KEY,
  rule_name VARCHAR(100) NOT NULL,
  symptoms_csv VARCHAR(255) NOT NULL,
  severity ENUM('low','medium','high') DEFAULT 'low',
  advice TEXT NOT NULL,
  red_flags TEXT DEFAULT NULL,
  recommend_meds_csv VARCHAR(255) DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS health_alerts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  description TEXT NOT NULL,
  prevention_tips TEXT NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  is_active TINYINT(1) DEFAULT 1
);

CREATE TABLE IF NOT EXISTS user_allergies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  allergy_key VARCHAR(80) NOT NULL,
  allergy_name VARCHAR(120) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_user (user_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS menstrual_cycles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE DEFAULT NULL,
  cycle_length INT DEFAULT 28,
  notes VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_user (user_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);



-- Fitness video module
CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  slug VARCHAR(60) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS courses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NOT NULL,
  title VARCHAR(120) NOT NULL,
  coach VARCHAR(80) NULL,
  difficulty ENUM('Beginner','Intermediate','Advanced') NOT NULL,
  total_minutes INT NOT NULL DEFAULT 0,
  lessons_count INT NOT NULL DEFAULT 0,
  description TEXT NULL,
  goals TEXT NULL,
  equipment TEXT NULL,
  precautions TEXT NULL,
  thumbnail_url VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
  INDEX idx_courses_category (category_id),
  INDEX idx_courses_difficulty (difficulty)
);

CREATE TABLE IF NOT EXISTS lessons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  course_id INT NOT NULL,
  title VARCHAR(120) NOT NULL,
  lesson_no INT NOT NULL,
  duration_minutes INT NOT NULL,
  youtube_url VARCHAR(255) NOT NULL,
  youtube_id VARCHAR(32) NULL,
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
  UNIQUE KEY uniq_course_lesson_no (course_id, lesson_no)
);

CREATE TABLE IF NOT EXISTS reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  course_id INT NOT NULL,
  user_id INT NULL,
  rating TINYINT NOT NULL,
  comment TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
  INDEX idx_reviews_course (course_id)
);

CREATE TABLE IF NOT EXISTS favorites (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  course_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_user_course (user_id, course_id),
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);


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

CREATE TABLE IF NOT EXISTS rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reward_name VARCHAR(100) NOT NULL,
    points_required INT NOT NULL,
    description VARCHAR(255)
);

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

INSERT INTO rewards (reward_name, points_required, description) VALUES
('Healthy Badge', 50, 'Awarded for maintaining healthy habits'),
('Fitness Coupon', 100, 'Discount coupon for fitness products'),
('Premium Access', 200, 'Unlock premium features');


-- Insert initial achievements (feel free to add more!)
INSERT INTO achievements (code, name, description, icon, color_class, required_points, required_steps, sort_order) VALUES
('starter', 'Healthy Starter', 'Earn a total of 100 points to begin your healthy journey', 'award', 'text-success', 100, 0, 10),
('walker_10k', '10K Step Master', 'Walk a total of 10,000 steps', 'footprints', 'text-info', 0, 10000, 20),
('point_collector', 'Point Collector', 'Earn a total of 300 points', 'gem', 'text-primary', 300, 0, 30),
('consistent', 'Consistency Star', 'Generate points for 7 consecutive days', 'star-fill', 'text-warning', 0, 0, 40),
('elite', 'Elite Walker', 'Earn a total of 600 points and reach Pro level', 'trophy-fill', 'text-danger', 600, 0, 50),
('marathon', 'Marathon Spirit', 'Walk a total of 50,000 steps', 'flag-fill', 'text-dark', 0, 50000, 60);


ALTER TABLE users
    ADD COLUMN full_name VARCHAR(100) DEFAULT NULL COMMENT 'Full name for display',
    ADD COLUMN birth_date DATE DEFAULT NULL COMMENT 'Birth date',
    ADD COLUMN phone VARCHAR(20) DEFAULT NULL COMMENT 'Phone number',
    ADD COLUMN address VARCHAR(255) DEFAULT NULL COMMENT 'Home address',
    ADD COLUMN country VARCHAR(50) DEFAULT NULL COMMENT 'Country',
    ADD COLUMN language VARCHAR(10) DEFAULT 'en' COMMENT 'Preferred language (e.g., en, zh)',
    ADD COLUMN height DECIMAL(6,2) DEFAULT NULL COMMENT 'Height in cm',
    ADD COLUMN weight DECIMAL(7,3) DEFAULT NULL COMMENT 'Weight in kg',
    ADD COLUMN gender ENUM('male', 'female', 'other') DEFAULT NULL COMMENT 'Gender',
    ADD COLUMN daily_step_goal INT DEFAULT 10000 COMMENT 'Personal daily step target',
    ADD COLUMN target_weight DECIMAL(7,3) DEFAULT NULL COMMENT 'Weight loss target (kg)',
    ADD COLUMN expected_weeks TINYINT UNSIGNED DEFAULT NULL COMMENT 'How many weeks to reach target';



-- Seed data for categories
INSERT IGNORE INTO categories (name, slug) VALUES
  ('Strength', 'strength'),
  ('Yoga', 'yoga'),
  ('HIIT', 'hiit'),
  ('Stretching', 'stretching'),
  ('Core', 'core'),
  ('Fat Burn', 'fat-burn');

-- Seed courses (one per category)
INSERT IGNORE INTO courses
  (id, category_id, title, coach, difficulty, total_minutes, lessons_count, description, goals, equipment, precautions, thumbnail_url)
VALUES
  (1, (SELECT id FROM categories WHERE slug='strength'), 'Strength Builder 5-Day', 'Coach Maya', 'Intermediate', 0, 0,
    'Build foundational strength with full-body circuits.', 'Strength, posture, consistency', 'Dumbbells or kettlebell optional', 'Keep spine neutral; scale weights down.', 'https://via.placeholder.com/640x360?text=Strength'),
  (2, (SELECT id FROM categories WHERE slug='yoga'), 'Calm Flow Yoga', 'Coach Aisha', 'Beginner', 0, 0,
    'Gentle vinyasa to improve mobility and breathing.', 'Mobility, relaxation, breathing', 'Yoga mat', 'Avoid holding breath; move within comfort.', 'https://via.placeholder.com/640x360?text=Yoga'),
  (3, (SELECT id FROM categories WHERE slug='hiit'), 'HIIT Torch', 'Coach Liam', 'Advanced', 0, 0,
    'High-intensity intervals to boost cardio and burn calories.', 'Cardio, calorie burn, endurance', 'Mat, towel, water', 'Skip jumps if joint pain; hydrate well.', 'https://via.placeholder.com/640x360?text=HIIT'),
  (4, (SELECT id FROM categories WHERE slug='stretching'), 'Daily Stretch Reset', 'Coach Emi', 'Beginner', 0, 0,
    'Daily stretch routine to ease stiffness and improve posture.', 'Flexibility, posture, recovery', 'Yoga mat optional', 'Move slowly; no pain in joints.', 'https://via.placeholder.com/640x360?text=Stretch'),
  (5, (SELECT id FROM categories WHERE slug='core'), 'Core Stability Lab', 'Coach Daniel', 'Intermediate', 0, 0,
    'Targeted core sessions for stability and balance.', 'Core strength, stability, balance', 'Mat', 'Keep lower back supported; modify plank time.', 'https://via.placeholder.com/640x360?text=Core'),
  (6, (SELECT id FROM categories WHERE slug='fat-burn'), 'Fat Burn Express', 'Coach Nina', 'Intermediate', 0, 0,
    'Fat-burning circuits with steady pacing and form cues.', 'Calorie burn, stamina, sweat', 'Mat, light dumbbells optional', 'Monitor heart rate; rest when needed.', 'https://via.placeholder.com/640x360?text=Fat+Burn');

-- Seed lessons (5 per course, total 30)
INSERT IGNORE INTO lessons
  (course_id, title, lesson_no, duration_minutes, youtube_url, youtube_id, notes)
VALUES
  -- Strength (course 1)
  (1, 'Full Body Dumbbell', 1, 20, 'https://www.youtube.com/watch?v=UItWltVZZmE', 'UItWltVZZmE', 'Warm up 5 min; focus on form'),
  (1, 'Upper Push Focus', 2, 18, 'https://www.youtube.com/watch?v=IODxDxX7oi4', 'IODxDxX7oi4', 'Control tempo, avoid joint lock'),
  (1, 'Legs & Glutes', 3, 22, 'https://www.youtube.com/watch?v=gC_L9qAHVJ8', 'gC_L9qAHVJ8', 'Drive through heels on squats'),
  (1, 'Back & Core Blend', 4, 16, 'https://www.youtube.com/watch?v=2vWK9Uftb0w', '2vWK9Uftb0w', 'Neutral spine on hinges'),
  (1, 'Total Body Finisher', 5, 15, 'https://www.youtube.com/watch?v=q20pLhdoEoY', 'q20pLhdoEoY', 'Short rest, lighter weight optional'),
  -- Yoga (course 2)
  (2, 'Morning Flow', 1, 15, 'https://www.youtube.com/watch?v=v7AYKMP6rOE', 'v7AYKMP6rOE', 'Focus on breath, light pace'),
  (2, 'Hip Opening', 2, 18, 'https://www.youtube.com/watch?v=4pKly2JojMw', '4pKly2JojMw', 'Use blocks if tight'),
  (2, 'Neck & Shoulder Relief', 3, 12, 'https://www.youtube.com/watch?v=EdSILZMyFMY', 'EdSILZMyFMY', 'Gentle range of motion'),
  (2, 'Balance & Core', 4, 20, 'https://www.youtube.com/watch?v=VaoV1PrYft4', 'VaoV1PrYft4', 'Engage core, micro-bend knees'),
  (2, 'Sleepy Time Stretch', 5, 14, 'https://www.youtube.com/watch?v=Fk9jGm4bCjM', 'Fk9jGm4bCjM', 'Low-light, slow transitions'),
  -- HIIT (course 3)
  (3, '20-min HIIT Burn', 1, 20, 'https://www.youtube.com/watch?v=ml6cT4AZdqI', 'ml6cT4AZdqI', '40s on / 20s off'),
  (3, 'Low-Impact HIIT', 2, 18, 'https://www.youtube.com/watch?v=2vWK9Uftb0w', '2vWK9Uftb0w', 'No jumps, focus on drive'),
  (3, 'Tabata Torch', 3, 16, 'https://www.youtube.com/watch?v=QOHJTIIfs9c', 'QOHJTIIfs9c', '20s on / 10s off'),
  (3, 'Cardio Core', 4, 22, 'https://www.youtube.com/watch?v=50kH47ZztHs', '50kH47ZztHs', 'High knees + plank combos'),
  (3, 'HIIT Finisher', 5, 14, 'https://www.youtube.com/watch?v=fcN37TxBE_s', 'fcN37TxBE_s', 'Short but intense, hydrate'),
  -- Stretching (course 4)
  (4, 'Full Body Stretch', 1, 15, 'https://www.youtube.com/watch?v=sTANio_2E0Q', 'sTANio_2E0Q', 'Hold 30-45s each'),
  (4, 'Desk Relief', 2, 12, 'https://www.youtube.com/watch?v=Z32cDfb5T1w', 'Z32cDfb5T1w', 'Neck/shoulder focus'),
  (4, 'Hip Flexor Focus', 3, 14, 'https://www.youtube.com/watch?v=j6u9Ju5w8pU', 'j6u9Ju5w8pU', 'Gentle lunges, no pain'),
  (4, 'Posterior Chain', 4, 16, 'https://www.youtube.com/watch?v=u5BumfRVp2E', 'u5BumfRVp2E', 'Hamstring & calf'),
  (4, 'Evening Unwind', 5, 18, 'https://www.youtube.com/watch?v=R0mMyV5OtcM', 'R0mMyV5OtcM', 'Slow breathing, low effort'),
  -- Core (course 5)
  (5, 'Core Foundations', 1, 12, 'https://www.youtube.com/watch?v=AnYl6Nk9GOA', 'AnYl6Nk9GOA', 'Brace, avoid neck strain'),
  (5, 'Plank Variations', 2, 14, 'https://www.youtube.com/watch?v=pSHjTRCQxIw', 'pSHjTRCQxIw', 'Short sets, quality form'),
  (5, 'Oblique Burner', 3, 16, 'https://www.youtube.com/watch?v=44mgUselcDU', '44mgUselcDU', 'Slow bicycle, side planks'),
  (5, 'Core & Glutes', 4, 18, 'https://www.youtube.com/watch?v=QKKZ9AGYTi4', 'QKKZ9AGYTi4', 'Hip thrust cues'),
  (5, 'Core Finisher', 5, 10, 'https://www.youtube.com/watch?v=6kALZikXxLc', '6kALZikXxLc', 'Short crunch intervals'),
  -- Fat Burn (course 6)
  (6, 'Fat Burn Kickstart', 1, 18, 'https://www.youtube.com/watch?v=fcN37TxBE_s', 'fcN37TxBE_s', 'Steady pace, low impact options'),
  (6, 'Cardio Sweat', 2, 20, 'https://www.youtube.com/watch?v=ml6cT4AZdqI', 'ml6cT4AZdqI', 'Intervals with modified jumps'),
  (6, 'Boxing Burn', 3, 22, 'https://www.youtube.com/watch?v=bkz4K4TnC0c', 'bkz4K4TnC0c', 'Guard up, snap punches'),
  (6, 'Fat Burn + Core', 4, 16, 'https://www.youtube.com/watch?v=H3jJ29oE8Zg', 'H3jJ29oE8Zg', 'Twists and planks mix'),
  (6, 'Express Burner', 5, 12, 'https://www.youtube.com/watch?v=j6u9Ju5w8pU', 'j6u9Ju5w8pU', 'Short sets, focus breathing');

-- Recompute total_minutes and lessons_count
UPDATE courses c
SET lessons_count = (
    SELECT COUNT(*) FROM lessons l WHERE l.course_id = c.id
  ),
  total_minutes = (
    SELECT COALESCE(SUM(l.duration_minutes), 0) FROM lessons l WHERE l.course_id = c.id
  );

-- Optional sample reviews (one per course)
INSERT IGNORE INTO reviews (course_id, user_id, rating, comment)
VALUES
  (1, NULL, 5, 'Great pacing and clear cues.'),
  (2, NULL, 5, 'Relaxing and beginner-friendly.'),
  (3, NULL, 4, 'Intense but manageable with mods.'),
  (4, NULL, 5, 'Helped my back feel better.'),
  (5, NULL, 4, 'Solid core focus, good progressions.'),
  (6, NULL, 4, 'Sweaty and fun, nice variety.');



-- =========================================================
-- Health Module seed data (from healthy.sql)
-- =========================================================

-- Optional sample user (needed for sample menstrual_cycles/user_allergies rows)
INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `created_at`) VALUES
(1, 'ZUO XIAOYE', 'zuoxyusm@student.usm.my', '$2y$10$zh5H69H1LsBODFjFeuoWuuEBYTd6DVySHHJdDh2KXsix2OvOlCxFa', '2025-12-29 14:33:14');

INSERT INTO `symptom_catalog` (`id`, `symptom_key`, `symptom_name`) VALUES
(1, 'fever', 'Fever'),
(2, 'cough', 'Cough'),
(3, 'headache', 'Headache'),
(4, 'sore_throat', 'Sore Throat'),
(5, 'fatigue', 'Fatigue');

INSERT INTO `medication_catalog` (`id`, `med_key`, `med_name`, `med_type`, `notes`) VALUES
(1, 'paracetamol', 'Paracetamol', 'OTC', 'Reduce fever and mild pain'),
(2, 'ibuprofen', 'Ibuprofen', 'OTC', 'Anti-inflammatory pain reliever'),
(3, 'antihistamine', 'Antihistamine', 'OTC', 'Relieves allergy symptoms');

INSERT INTO `symptom_rules` (`id`, `rule_name`, `symptoms_csv`, `severity`, `advice`, `red_flags`, `recommend_meds_csv`) VALUES
(1, 'Flu-like symptoms', 'fever,cough,fatigue', 'medium', 'Rest well, drink warm fluids, and monitor symptoms.', 'If fever lasts more than 3 days, consult a doctor.', 'paracetamol'),
(2, 'Mild headache', 'headache', 'low', 'Reduce screen time and stay hydrated.', NULL, 'paracetamol,ibuprofen');

INSERT INTO `health_alerts` (`id`, `title`, `description`, `prevention_tips`, `start_date`, `end_date`, `is_active`) VALUES
(1, 'Seasonal Influenza Alert', 'Increase in flu cases reported recently.', 'Wash hands frequently, avoid crowded places, and rest well.', '2025-12-30', '2026-01-29', 1);

INSERT INTO `user_allergies` (`id`, `user_id`, `allergy_key`, `allergy_name`, `created_at`) VALUES
(1, 1, 'seafood', 'seafood', '2025-12-30 07:24:19'),
(2, 1, 'penicillin', 'penicillin', '2025-12-30 07:26:37');

INSERT INTO `menstrual_cycles` (`id`, `user_id`, `start_date`, `end_date`, `cycle_length`, `notes`, `created_at`) VALUES
(1, 1, '2026-01-01', '2026-01-08', 28, NULL, '2025-12-30 08:12:03');

INSERT INTO `contraindications` (`id`, `allergy_key`, `med_key`, `note`) VALUES
(1, 'penicillin', 'paracetamol', 'Consult a doctor before use');
