-- Training Module Database Schema
-- Compatible with MySQL 5.7+ / MariaDB 10.2+
-- Character set: utf8mb4

USE mysihat;

-- Extend users table for training preferences
-- Check if columns exist before adding (MySQL doesn't support IF NOT EXISTS in ALTER TABLE)
SET @dbname = DATABASE();
SET @tablename = 'users';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = 'equipment_level')
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN equipment_level ENUM(\'home\',\'dumbbell\',\'gym\') NOT NULL DEFAULT \'home\' AFTER password_hash')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = 'default_days_per_week')
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN default_days_per_week TINYINT NOT NULL DEFAULT 4 AFTER equipment_level')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = 'default_session_duration')
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN default_session_duration TINYINT NOT NULL DEFAULT 45 AFTER default_days_per_week')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = 'prefer_home')
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN prefer_home TINYINT(1) NOT NULL DEFAULT 1 AFTER default_session_duration')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Exercises table
CREATE TABLE IF NOT EXISTS exercises (
  exercise_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  pattern ENUM('squat','hinge','push','pull','core','cardio') NOT NULL,
  equipment ENUM('home','dumbbell','gym') NOT NULL,
  difficulty TINYINT NOT NULL DEFAULT 1,
  alt_exercise_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ex_alt FOREIGN KEY (alt_exercise_id) REFERENCES exercises(exercise_id) ON DELETE SET NULL,
  INDEX idx_pattern_equipment (pattern, equipment)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Plans table
CREATE TABLE IF NOT EXISTS plans (
  plan_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  week_start_date DATE NOT NULL,
  days_per_week TINYINT NOT NULL,
  session_duration TINYINT NOT NULL,
  mode ENUM('home','gym') NOT NULL,
  created_from ENUM('manual','recommendation') NOT NULL DEFAULT 'manual',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_week (user_id, week_start_date),
  CONSTRAINT fk_plan_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_week (user_id, week_start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Plan days table
CREATE TABLE IF NOT EXISTS plan_days (
  plan_day_id INT AUTO_INCREMENT PRIMARY KEY,
  plan_id INT NOT NULL,
  date DATE NOT NULL,
  day_index TINYINT NOT NULL,
  estimated_minutes TINYINT NOT NULL,
  CONSTRAINT fk_day_plan FOREIGN KEY (plan_id) REFERENCES plans(plan_id) ON DELETE CASCADE,
  INDEX idx_plan_date (plan_id, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Plan items table
CREATE TABLE IF NOT EXISTS plan_items (
  plan_item_id INT AUTO_INCREMENT PRIMARY KEY,
  plan_day_id INT NOT NULL,
  exercise_id INT NOT NULL,
  target_sets TINYINT NOT NULL,
  target_reps TINYINT NOT NULL,
  order_no TINYINT NOT NULL,
  CONSTRAINT fk_item_day FOREIGN KEY (plan_day_id) REFERENCES plan_days(plan_day_id) ON DELETE CASCADE,
  CONSTRAINT fk_item_ex FOREIGN KEY (exercise_id) REFERENCES exercises(exercise_id) ON DELETE RESTRICT,
  INDEX idx_day_order (plan_day_id, order_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Workouts table
CREATE TABLE IF NOT EXISTS workouts (
  workout_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  plan_day_id INT NULL,
  start_time DATETIME NOT NULL,
  end_time DATETIME NULL,
  duration_min SMALLINT NULL,
  status ENUM('completed','abandoned') NOT NULL DEFAULT 'completed',
  CONSTRAINT fk_workout_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_workout_day FOREIGN KEY (plan_day_id) REFERENCES plan_days(plan_day_id) ON DELETE SET NULL,
  INDEX idx_user_time (user_id, start_time),
  INDEX idx_day (plan_day_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Workout items table
CREATE TABLE IF NOT EXISTS workout_items (
  workout_item_id INT AUTO_INCREMENT PRIMARY KEY,
  workout_id INT NOT NULL,
  exercise_id INT NOT NULL,
  is_completed TINYINT(1) NOT NULL DEFAULT 0,
  rpe TINYINT NULL,
  sets TINYINT NULL,
  reps TINYINT NULL,
  weight DECIMAL(6,2) NULL,
  CONSTRAINT fk_wi_workout FOREIGN KEY (workout_id) REFERENCES workouts(workout_id) ON DELETE CASCADE,
  CONSTRAINT fk_wi_ex FOREIGN KEY (exercise_id) REFERENCES exercises(exercise_id) ON DELETE RESTRICT,
  INDEX idx_workout (workout_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

