-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主机： 127.0.0.1
-- 生成日期： 2026-01-03 10:23:33
-- 服务器版本： 10.4.32-MariaDB
-- PHP 版本： 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `mysihat`
--

-- --------------------------------------------------------

--
-- 表的结构 `contraindications`
--

CREATE TABLE `contraindications` (
  `id` int(11) NOT NULL,
  `allergy_key` varchar(80) NOT NULL,
  `med_key` varchar(50) NOT NULL,
  `note` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `contraindications`
--

INSERT INTO `contraindications` (`id`, `allergy_key`, `med_key`, `note`) VALUES
(1, 'penicillin', 'paracetamol', 'Consult a doctor before use');

-- --------------------------------------------------------

--
-- 表的结构 `daily_steps`
--

CREATE TABLE `daily_steps` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `step_date` date NOT NULL,
  `steps` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `health_alerts`
--

CREATE TABLE `health_alerts` (
  `id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `prevention_tips` text NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `health_alerts`
--

INSERT INTO `health_alerts` (`id`, `title`, `description`, `prevention_tips`, `start_date`, `end_date`, `is_active`) VALUES
(1, 'Seasonal Influenza Alert', 'Increase in flu cases reported recently.', 'Wash hands frequently, avoid crowded places, and rest well.', '2025-12-30', '2026-01-29', 1);

-- --------------------------------------------------------

--
-- 表的结构 `meals`
--

CREATE TABLE `meals` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `meal_time` datetime NOT NULL,
  `meal_text` varchar(255) NOT NULL,
  `estimated_kcal` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `medication_catalog`
--

CREATE TABLE `medication_catalog` (
  `id` int(11) NOT NULL,
  `med_key` varchar(50) NOT NULL,
  `med_name` varchar(100) NOT NULL,
  `med_type` varchar(30) DEFAULT 'OTC',
  `notes` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `medication_catalog`
--

INSERT INTO `medication_catalog` (`id`, `med_key`, `med_name`, `med_type`, `notes`) VALUES
(1, 'paracetamol', 'Paracetamol', 'OTC', 'Reduce fever and mild pain'),
(2, 'ibuprofen', 'Ibuprofen', 'OTC', 'Anti-inflammatory pain reliever'),
(3, 'antihistamine', 'Antihistamine', 'OTC', 'Relieves allergy symptoms');

-- --------------------------------------------------------

--
-- 表的结构 `menstrual_cycles`
--

CREATE TABLE `menstrual_cycles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `cycle_length` int(11) DEFAULT 28,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `menstrual_cycles`
--

INSERT INTO `menstrual_cycles` (`id`, `user_id`, `start_date`, `end_date`, `cycle_length`, `notes`, `created_at`) VALUES
(1, 1, '2026-01-01', '2026-01-08', 28, NULL, '2025-12-30 08:12:03');

-- --------------------------------------------------------

--
-- 表的结构 `points_ledger`
--

CREATE TABLE `points_ledger` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `point_date` date NOT NULL,
  `points_earned` int(11) NOT NULL DEFAULT 0,
  `points_spent` int(11) NOT NULL DEFAULT 0,
  `reason` varchar(120) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `symptom_catalog`
--

CREATE TABLE `symptom_catalog` (
  `id` int(11) NOT NULL,
  `symptom_key` varchar(50) NOT NULL,
  `symptom_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `symptom_catalog`
--

INSERT INTO `symptom_catalog` (`id`, `symptom_key`, `symptom_name`) VALUES
(1, 'fever', 'Fever'),
(2, 'cough', 'Cough'),
(3, 'headache', 'Headache'),
(4, 'sore_throat', 'Sore Throat'),
(5, 'fatigue', 'Fatigue');

-- --------------------------------------------------------

--
-- 表的结构 `symptom_rules`
--

CREATE TABLE `symptom_rules` (
  `id` int(11) NOT NULL,
  `rule_name` varchar(100) NOT NULL,
  `symptoms_csv` varchar(255) NOT NULL,
  `severity` enum('low','medium','high') DEFAULT 'low',
  `advice` text NOT NULL,
  `red_flags` text DEFAULT NULL,
  `recommend_meds_csv` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `symptom_rules`
--

INSERT INTO `symptom_rules` (`id`, `rule_name`, `symptoms_csv`, `severity`, `advice`, `red_flags`, `recommend_meds_csv`) VALUES
(1, 'Flu-like symptoms', 'fever,cough,fatigue', 'medium', 'Rest well, drink warm fluids, and monitor symptoms.', 'If fever lasts more than 3 days, consult a doctor.', 'paracetamol'),
(2, 'Mild headache', 'headache', 'low', 'Reduce screen time and stay hydrated.', NULL, 'paracetamol,ibuprofen');

-- --------------------------------------------------------

--
-- 表的结构 `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(80) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `created_at`) VALUES
(1, 'ZUO XIAOYE', 'zuoxyusm@student.usm.my', '$2y$10$zh5H69H1LsBODFjFeuoWuuEBYTd6DVySHHJdDh2KXsix2OvOlCxFa', '2025-12-29 14:33:14');

-- --------------------------------------------------------

--
-- 表的结构 `user_allergies`
--

CREATE TABLE `user_allergies` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `allergy_key` varchar(80) NOT NULL,
  `allergy_name` varchar(120) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `user_allergies`
--

INSERT INTO `user_allergies` (`id`, `user_id`, `allergy_key`, `allergy_name`, `created_at`) VALUES
(1, 1, 'seafood', 'seafood', '2025-12-30 07:24:19'),
(2, 1, 'penicillin', 'penicillin', '2025-12-30 07:26:37');

--
-- 转储表的索引
--

--
-- 表的索引 `contraindications`
--
ALTER TABLE `contraindications`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `daily_steps`
--
ALTER TABLE `daily_steps`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_date` (`user_id`,`step_date`);

--
-- 表的索引 `health_alerts`
--
ALTER TABLE `health_alerts`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `meals`
--
ALTER TABLE `meals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- 表的索引 `medication_catalog`
--
ALTER TABLE `medication_catalog`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `med_key` (`med_key`);

--
-- 表的索引 `menstrual_cycles`
--
ALTER TABLE `menstrual_cycles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- 表的索引 `points_ledger`
--
ALTER TABLE `points_ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- 表的索引 `symptom_catalog`
--
ALTER TABLE `symptom_catalog`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `symptom_key` (`symptom_key`);

--
-- 表的索引 `symptom_rules`
--
ALTER TABLE `symptom_rules`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- 表的索引 `user_allergies`
--
ALTER TABLE `user_allergies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `contraindications`
--
ALTER TABLE `contraindications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `daily_steps`
--
ALTER TABLE `daily_steps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `health_alerts`
--
ALTER TABLE `health_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `meals`
--
ALTER TABLE `meals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `medication_catalog`
--
ALTER TABLE `medication_catalog`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 使用表AUTO_INCREMENT `menstrual_cycles`
--
ALTER TABLE `menstrual_cycles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `points_ledger`
--
ALTER TABLE `points_ledger`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `symptom_catalog`
--
ALTER TABLE `symptom_catalog`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- 使用表AUTO_INCREMENT `symptom_rules`
--
ALTER TABLE `symptom_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 使用表AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `user_allergies`
--
ALTER TABLE `user_allergies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 限制导出的表
--

--
-- 限制表 `daily_steps`
--
ALTER TABLE `daily_steps`
  ADD CONSTRAINT `daily_steps_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- 限制表 `meals`
--
ALTER TABLE `meals`
  ADD CONSTRAINT `meals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- 限制表 `points_ledger`
--
ALTER TABLE `points_ledger`
  ADD CONSTRAINT `points_ledger_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
