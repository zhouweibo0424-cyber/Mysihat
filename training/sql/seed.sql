USE mysihat;

-- Demo user
INSERT INTO users (id, name, email, password_hash)
VALUES (1, 'Demo User', 'demo@mysihat.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
ON DUPLICATE KEY UPDATE name = VALUES(name);

UPDATE users SET
  equipment_level = 'home',
  default_days_per_week = 4,
  default_session_duration = 45,
  prefer_home = 1
WHERE id = 1;

-- -----------------------
-- Exercises: Home
-- -----------------------
INSERT INTO exercises (name, pattern, equipment, difficulty, alt_exercise_id) VALUES
('Air Squat', 'squat', 'home', 1, NULL),
('Split Squat', 'squat', 'home', 2, NULL),
('Glute Bridge', 'hinge', 'home', 1, NULL),
('Hip Hinge', 'hinge', 'home', 2, NULL),
('Push-up', 'push', 'home', 2, NULL),
('Pike Push-up', 'push', 'home', 3, NULL),
('Towel Row', 'pull', 'home', 2, NULL),
('Superman Row', 'pull', 'home', 1, NULL),
('Plank', 'core', 'home', 2, NULL),
('Dead Bug', 'core', 'home', 2, NULL),
('Jumping Jacks', 'cardio', 'home', 1, NULL),
('High Knees', 'cardio', 'home', 2, NULL),
('Mountain Climbers', 'cardio', 'home', 2, NULL);

-- 先把 Home 的替代动作 id 取出来（避免 #1093）
SET @air_squat_id    := (SELECT exercise_id FROM exercises WHERE name='Air Squat' LIMIT 1);
SET @glute_bridge_id := (SELECT exercise_id FROM exercises WHERE name='Glute Bridge' LIMIT 1);
SET @pushup_id       := (SELECT exercise_id FROM exercises WHERE name='Push-up' LIMIT 1);
SET @pike_pushup_id  := (SELECT exercise_id FROM exercises WHERE name='Pike Push-up' LIMIT 1);
SET @towel_row_id    := (SELECT exercise_id FROM exercises WHERE name='Towel Row' LIMIT 1);
SET @plank_id        := (SELECT exercise_id FROM exercises WHERE name='Plank' LIMIT 1);
SET @high_knees_id   := (SELECT exercise_id FROM exercises WHERE name='High Knees' LIMIT 1);

-- -----------------------
-- Exercises: Dumbbell
-- -----------------------
INSERT INTO exercises (name, pattern, equipment, difficulty, alt_exercise_id) VALUES
('DB Goblet Squat',   'squat',  'dumbbell', 2, @air_squat_id),
('DB RDL',            'hinge',  'dumbbell', 2, @glute_bridge_id),
('DB Floor Press',    'push',   'dumbbell', 2, @pushup_id),
('DB Shoulder Press', 'push',   'dumbbell', 3, @pike_pushup_id),
('DB Row',            'pull',   'dumbbell', 2, @towel_row_id),
('DB Russian Twist',  'core',   'dumbbell', 2, @plank_id),
('DB Step-up',        'cardio', 'dumbbell', 2, @high_knees_id);

-- 再取 Dumbbell 的替代动作 id（给 Gym 用）
SET @db_goblet_squat_id := (SELECT exercise_id FROM exercises WHERE name='DB Goblet Squat' LIMIT 1);
SET @db_rdl_id          := (SELECT exercise_id FROM exercises WHERE name='DB RDL' LIMIT 1);
SET @db_floor_press_id  := (SELECT exercise_id FROM exercises WHERE name='DB Floor Press' LIMIT 1);
SET @db_row_id          := (SELECT exercise_id FROM exercises WHERE name='DB Row' LIMIT 1);
SET @jumping_jacks_id   := (SELECT exercise_id FROM exercises WHERE name='Jumping Jacks' LIMIT 1);

-- -----------------------
-- Exercises: Gym
-- -----------------------
INSERT INTO exercises (name, pattern, equipment, difficulty, alt_exercise_id) VALUES
('Barbell Squat', 'squat',  'gym', 3, @db_goblet_squat_id),
('Leg Press',     'squat',  'gym', 2, @db_goblet_squat_id),
('Deadlift',      'hinge',  'gym', 4, @db_rdl_id),
('Bench Press',   'push',   'gym', 3, @db_floor_press_id),
('Lat Pulldown',  'pull',   'gym', 3, @db_row_id),
('Seated Row',    'pull',   'gym', 2, @db_row_id),
('Cable Crunch',  'core',   'gym', 2, @plank_id),
('Treadmill',     'cardio', 'gym', 1, @jumping_jacks_id),
('Bike',          'cardio', 'gym', 1, @high_knees_id);
