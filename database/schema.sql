-- MySihat AI (Web version) database setup
CREATE DATABASE IF NOT EXISTS mysihat
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE mysihat;

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
