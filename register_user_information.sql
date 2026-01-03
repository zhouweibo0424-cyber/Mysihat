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
