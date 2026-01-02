-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主机： localhost
-- 生成日期： 2025-12-31 11:03:36
-- 服务器版本： 10.4.28-MariaDB
-- PHP 版本： 8.1.17

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `diet_assistant`
--

-- --------------------------------------------------------

--
-- 表的结构 `diet_goals`
--

CREATE TABLE `diet_goals` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `goal_type` varchar(50) NOT NULL,
  `daily_calorie_target` int(11) NOT NULL,
  `protein_target_g` int(11) NOT NULL,
  `carbs_target_g` int(11) NOT NULL,
  `fat_target_g` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `diet_goals`
--

INSERT INTO `diet_goals` (`id`, `user_id`, `goal_type`, `daily_calorie_target`, `protein_target_g`, `carbs_target_g`, `fat_target_g`, `created_at`, `updated_at`) VALUES
(1, 1, 'maintain', 1990, 140, 222, 60, '2025-12-29 14:48:19', '2025-12-30 18:10:32');

-- --------------------------------------------------------

--
-- 表的结构 `diet_logs`
--

CREATE TABLE `diet_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `log_date` date NOT NULL,
  `food_id` int(11) DEFAULT NULL,
  `food_name` varchar(255) DEFAULT NULL,
  `servings` decimal(8,2) NOT NULL DEFAULT 1.00,
  `calories` int(11) NOT NULL,
  `protein_g` int(11) NOT NULL,
  `carbs_g` int(11) NOT NULL,
  `fat_g` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `diet_logs`
--

INSERT INTO `diet_logs` (`id`, `user_id`, `log_date`, `food_id`, `food_name`, `servings`, `calories`, `protein_g`, `carbs_g`, `fat_g`, `created_at`) VALUES
(1, 1, '2025-12-28', 4, 'Egg (1 large)', 2.50, 72, 6, 0, 5, '2025-12-29 16:42:07'),
(2, 1, '2025-12-28', 14, 'White Rice (cooked, 100g)', 1.83, 130, 2, 28, 0, '2025-12-29 16:47:34'),
(5, 1, '2025-12-28', 3, 'Tuna (canned in water, 100g)', 2.09, 116, 25, 0, 1, '2025-12-29 16:47:34'),
(9, 1, '2025-12-28', 1, 'Chicken Breast (cooked, 100g)', 1.74, 165, 31, 0, 4, '2025-12-29 16:47:34'),
(10, 1, '2025-12-28', 10, 'Lentils (cooked, 100g)', 0.84, 116, 9, 20, 0, '2025-12-29 16:47:34'),
(21, 1, '2025-12-28', 26, 'Almonds (28g)', 1.00, 164, 6, 6, 14, '2025-12-30 16:53:32'),
(22, 1, '2025-12-29', 18, 'Apple (1 medium)', 1.00, 95, 1, 25, 0, '2025-12-30 16:54:03');

-- --------------------------------------------------------

--
-- 表的结构 `food_items`
--

CREATE TABLE `food_items` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `calories` int(11) NOT NULL,
  `protein_g` int(11) NOT NULL,
  `carbs_g` int(11) NOT NULL,
  `fat_g` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `food_items`
--

INSERT INTO `food_items` (`id`, `name`, `calories`, `protein_g`, `carbs_g`, `fat_g`, `created_at`) VALUES
(1, 'Chicken Breast (cooked, 100g)', 165, 31, 0, 4, '2025-12-29 14:42:50'),
(2, 'Salmon (100g)', 208, 20, 0, 13, '2025-12-29 14:42:50'),
(3, 'Tuna (canned in water, 100g)', 116, 25, 0, 1, '2025-12-29 14:42:50'),
(4, 'Egg (1 large)', 72, 6, 0, 5, '2025-12-29 14:42:50'),
(5, 'Greek Yogurt (plain, 170g)', 100, 17, 6, 1, '2025-12-29 14:42:50'),
(6, 'Milk (low-fat, 250ml)', 102, 8, 12, 2, '2025-12-29 14:42:50'),
(7, 'Cheddar Cheese (30g)', 121, 7, 0, 10, '2025-12-29 14:42:50'),
(8, 'Tofu (firm, 100g)', 144, 16, 3, 9, '2025-12-29 14:42:50'),
(9, 'Tempeh (100g)', 193, 20, 9, 11, '2025-12-29 14:42:50'),
(10, 'Lentils (cooked, 100g)', 116, 9, 20, 0, '2025-12-29 14:42:50'),
(11, 'Chickpeas (cooked, 100g)', 164, 9, 27, 3, '2025-12-29 14:42:50'),
(12, 'Black Beans (cooked, 100g)', 132, 9, 24, 1, '2025-12-29 14:42:50'),
(13, 'Brown Rice (cooked, 100g)', 111, 3, 23, 1, '2025-12-29 14:42:50'),
(14, 'White Rice (cooked, 100g)', 130, 2, 28, 0, '2025-12-29 14:42:50'),
(15, 'Oats (dry, 40g)', 150, 5, 27, 3, '2025-12-29 14:42:50'),
(16, 'Whole Wheat Bread (1 slice)', 80, 4, 14, 1, '2025-12-29 14:42:50'),
(17, 'Banana (1 medium)', 105, 1, 27, 0, '2025-12-29 14:42:50'),
(18, 'Apple (1 medium)', 95, 1, 25, 0, '2025-12-29 14:42:50'),
(19, 'Orange (1 medium)', 62, 1, 15, 0, '2025-12-29 14:42:50'),
(20, 'Blueberries (100g)', 57, 1, 15, 0, '2025-12-29 14:42:50'),
(21, 'Broccoli (100g)', 34, 3, 7, 0, '2025-12-29 14:42:50'),
(22, 'Spinach (100g)', 23, 3, 4, 0, '2025-12-29 14:42:50'),
(23, 'Carrots (100g)', 41, 1, 10, 0, '2025-12-29 14:42:50'),
(24, 'Tomato (100g)', 18, 1, 4, 0, '2025-12-29 14:42:50'),
(25, 'Avocado (100g)', 160, 2, 9, 15, '2025-12-29 14:42:50'),
(26, 'Almonds (28g)', 164, 6, 6, 14, '2025-12-29 14:42:50'),
(27, 'Peanut Butter (1 tbsp, 16g)', 94, 4, 3, 8, '2025-12-29 14:42:50'),
(28, 'Olive Oil (1 tbsp, 14g)', 119, 0, 0, 14, '2025-12-29 14:42:50'),
(29, 'Sweet Potato (baked, 100g)', 90, 2, 21, 0, '2025-12-29 14:42:50'),
(30, 'Pasta (cooked, 100g)', 131, 5, 25, 1, '2025-12-29 14:42:50'),
(31, 'Edamame (boiled, 100g)', 122, 12, 9, 5, '2025-12-29 15:25:09'),
(32, 'Cushaw Squash (cooked, 100g)', 45, 1, 12, 0, '2025-12-30 15:30:53');

-- --------------------------------------------------------

--
-- 表的结构 `meal_recipes`
--

CREATE TABLE `meal_recipes` (
  `id` int(11) NOT NULL,
  `recipe_name` varchar(255) NOT NULL,
  `ingredients` text NOT NULL,
  `instructions` text NOT NULL,
  `calories` int(11) NOT NULL,
  `protein_g` int(11) NOT NULL,
  `carbs_g` int(11) NOT NULL,
  `fat_g` int(11) NOT NULL,
  `is_healthy` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `meal_recipes`
--

INSERT INTO `meal_recipes` (`id`, `recipe_name`, `ingredients`, `instructions`, `calories`, `protein_g`, `carbs_g`, `fat_g`, `is_healthy`, `created_at`) VALUES
(1, 'Egg Veggie Omelette', 'Egg 2 (100 g); Spinach 1 cup (30 g); Tomato 1/2 (80 g); Onion 1/4 (30 g); Olive oil 1 tsp (5 ml); Black pepper 1/4 tsp; Salt optional', '1) Beat eggs with pepper. 2) Saute onion and tomato with olive oil. 3) Add spinach until wilted. 4) Pour eggs and cook until set. 5) Fold and serve.', 280, 20, 10, 16, 1, '2025-12-29 16:59:57'),
(2, 'Greek Yogurt Berry Bowl', 'Greek yogurt 200 g; Mixed berries 150 g; Chia seeds 1 tbsp (12 g); Honey 1 tsp (optional); Cinnamon 1/4 tsp', '1) Add yogurt to a bowl. 2) Top with berries and chia seeds. 3) Add cinnamon and honey if desired. 4) Rest 3 minutes and eat.', 320, 24, 35, 8, 1, '2025-12-29 16:59:57'),
(3, 'Overnight Oats Protein Jar', 'Rolled oats 60 g; Milk 200 ml; Greek yogurt 100 g; Banana 1/2 (60 g); Peanut butter 1 tbsp (16 g); Cinnamon 1/4 tsp', '1) Mix oats, milk, and yogurt in a jar. 2) Stir in cinnamon. 3) Top with banana and peanut butter. 4) Refrigerate overnight.', 520, 28, 62, 18, 1, '2025-12-29 16:59:57'),
(4, 'Chicken Salad Bowl', 'Chicken breast 150 g; Mixed greens 2 cups (80 g); Cucumber 100 g; Cherry tomato 120 g; Olive oil 1 tbsp (15 ml); Lemon juice 1 tbsp (15 ml); Black pepper 1/4 tsp; Salt optional', '1) Cook chicken and slice. 2) Combine greens and vegetables. 3) Mix olive oil and lemon juice as dressing. 4) Top with chicken and toss.', 420, 45, 18, 18, 1, '2025-12-29 16:59:57'),
(5, 'Salmon Rice Plate', 'Salmon 150 g; Cooked brown rice 150 g; Broccoli 200 g; Olive oil 1 tsp (5 ml); Garlic 1 clove; Lemon 1/2; Black pepper 1/4 tsp', '1) Season salmon with pepper and lemon. 2) Bake or pan-sear until cooked. 3) Steam broccoli. 4) Serve with rice and drizzle with a little olive oil.', 610, 42, 55, 22, 1, '2025-12-29 16:59:57'),
(6, 'Tuna Avocado Toast', 'Whole grain bread 2 slices (70 g); Tuna (in water) 120 g drained; Avocado 1/2 (80 g); Greek yogurt 2 tbsp (30 g); Lemon juice 1 tsp; Black pepper 1/4 tsp', '1) Toast bread. 2) Mix tuna with yogurt, lemon, and pepper. 3) Mash avocado onto toast. 4) Top with tuna mixture.', 520, 40, 45, 20, 1, '2025-12-29 16:59:57'),
(7, 'Tofu Broccoli Stir-Fry', 'Firm tofu 200 g; Broccoli 200 g; Carrot 120 g; Garlic 2 cloves; Ginger 1 tsp; Low-sodium soy sauce 1.5 tbsp; Sesame seeds 1 tsp (optional)', '1) Cube tofu and pat dry. 2) Stir-fry garlic and ginger. 3) Add broccoli and carrot with a splash of water. 4) Add tofu and soy sauce. 5) Finish with sesame seeds.', 380, 24, 28, 16, 1, '2025-12-29 16:59:57'),
(8, 'Lentil Veggie Soup', 'Cooked lentils 250 g; Onion 80 g; Carrot 120 g; Celery 80 g; Tomato canned 200 g; Vegetable broth 600 ml; Olive oil 1 tsp (5 ml); Black pepper 1/4 tsp', '1) Saute onion, carrot, and celery. 2) Add tomatoes and broth. 3) Add lentils and simmer 12 minutes. 4) Season and serve.', 410, 22, 70, 8, 1, '2025-12-29 16:59:57'),
(9, 'Chickpea Cucumber Salad', 'Chickpeas cooked 200 g; Cucumber 150 g; Tomato 150 g; Red onion 40 g; Olive oil 1 tbsp (15 ml); Lemon juice 1 tbsp (15 ml); Parsley 10 g; Black pepper 1/4 tsp', '1) Combine chickpeas and chopped vegetables. 2) Mix olive oil and lemon juice. 3) Toss with parsley and pepper. 4) Chill 10 minutes.', 430, 18, 58, 14, 1, '2025-12-29 16:59:57'),
(10, 'Turkey Quinoa Bowl', 'Lean ground turkey 150 g; Cooked quinoa 170 g; Spinach 1 cup (30 g); Bell pepper 120 g; Olive oil 1 tsp (5 ml); Paprika 1/2 tsp; Black pepper 1/4 tsp', '1) Cook turkey with paprika and pepper. 2) Warm quinoa. 3) Saute pepper briefly and add spinach to wilt. 4) Assemble bowl and drizzle olive oil.', 520, 42, 50, 18, 1, '2025-12-29 16:59:57'),
(11, 'Egg Fried Rice (Light)', 'Cooked rice 180 g; Egg 2 (100 g); Peas 80 g; Carrot 80 g; Low-sodium soy sauce 1 tbsp; Olive oil 1 tsp (5 ml); Green onion 10 g', '1) Heat oil, scramble eggs. 2) Add vegetables and stir. 3) Add rice and soy sauce. 4) Toss until hot. 5) Top with green onion.', 540, 22, 78, 16, 1, '2025-12-29 16:59:57'),
(12, 'Oat Banana Pancakes', 'Rolled oats 60 g; Banana 1 (120 g); Egg 1 (50 g); Baking powder 1/2 tsp; Milk 80 ml; Olive oil spray or 1 tsp', '1) Blend oats into flour. 2) Mix banana, egg, milk, and baking powder. 3) Cook small pancakes on a non-stick pan. 4) Serve warm.', 410, 16, 65, 10, 1, '2025-12-29 16:59:57'),
(13, 'Shrimp Zucchini Pasta', 'Shrimp 180 g; Zucchini 2 medium (400 g); Garlic 2 cloves; Olive oil 1 tsp (5 ml); Lemon juice 1 tbsp; Black pepper 1/4 tsp', '1) Spiralize zucchini. 2) Saute garlic, add shrimp until pink. 3) Add zucchini noodles 2 minutes. 4) Add lemon and pepper and serve.', 330, 36, 12, 14, 1, '2025-12-29 16:59:57'),
(14, 'Baked Sweet Potato Chicken', 'Sweet potato 250 g; Chicken breast 150 g; Greek yogurt 2 tbsp (30 g); Garlic powder 1/2 tsp; Black pepper 1/4 tsp; Olive oil 1 tsp (5 ml)', '1) Bake sweet potato until soft. 2) Season and cook chicken, then shred. 3) Mix yogurt with garlic powder. 4) Top potato with chicken and yogurt sauce.', 520, 45, 55, 12, 1, '2025-12-29 16:59:57'),
(15, 'Tofu Curry (Light Coconut)', 'Firm tofu 200 g; Mixed vegetables 250 g; Light coconut milk 150 ml; Curry powder 1 tbsp; Tomato paste 1 tbsp; Salt optional', '1) Saute curry powder briefly. 2) Add vegetables with splash of water. 3) Add tofu cubes. 4) Add coconut milk and tomato paste. 5) Simmer 8 minutes.', 460, 22, 38, 22, 1, '2025-12-29 16:59:57'),
(16, 'Black Bean Burrito Bowl', 'Cooked black beans 220 g; Cooked brown rice 150 g; Corn 80 g; Tomato salsa 120 g; Lettuce 1 cup (40 g); Greek yogurt 2 tbsp (30 g); Lime juice 1 tbsp', '1) Warm beans and rice. 2) Assemble with corn, salsa, and lettuce. 3) Add yogurt and lime. 4) Mix and serve.', 560, 24, 92, 10, 1, '2025-12-29 16:59:57'),
(17, 'Tempeh Veggie Bowl', 'Tempeh 150 g; Cooked quinoa 170 g; Broccoli 150 g; Carrot 120 g; Low-sodium soy sauce 1 tbsp; Olive oil 1 tsp (5 ml)', '1) Pan-sear tempeh slices. 2) Steam vegetables. 3) Combine quinoa and vegetables. 4) Add tempeh and soy sauce.', 560, 32, 62, 20, 1, '2025-12-29 16:59:57'),
(18, 'Cottage Cheese Fruit Plate', 'Low-fat cottage cheese 200 g; Apple 1 (180 g); Almonds 15 g; Cinnamon 1/4 tsp', '1) Slice apple. 2) Serve with cottage cheese. 3) Sprinkle cinnamon and almonds. 4) Eat as a balanced snack.', 380, 28, 38, 12, 1, '2025-12-29 16:59:57'),
(19, 'Chicken Veggie Wrap', 'Whole wheat wrap 1 (70 g); Chicken breast 120 g; Lettuce 40 g; Tomato 100 g; Cucumber 80 g; Greek yogurt 2 tbsp (30 g); Black pepper 1/4 tsp', '1) Mix yogurt and pepper as sauce. 2) Add chicken and vegetables to wrap. 3) Drizzle sauce. 4) Roll and serve.', 460, 38, 48, 12, 1, '2025-12-29 16:59:57'),
(20, 'Salmon Yogurt Dill Salad', 'Cooked salmon 150 g; Greek yogurt 120 g; Dill 5 g; Cucumber 120 g; Lemon juice 1 tbsp; Black pepper 1/4 tsp', '1) Flake salmon. 2) Mix yogurt, dill, lemon, and pepper. 3) Add cucumber. 4) Toss and chill 10 minutes.', 430, 40, 10, 22, 1, '2025-12-29 16:59:57'),
(21, 'Spinach Tomato Pasta (Whole Wheat)', 'Whole wheat pasta cooked 220 g; Spinach 2 cups (60 g); Tomato sauce 200 g; Garlic 2 cloves; Olive oil 1 tsp (5 ml); Parmesan 10 g (optional)', '1) Saute garlic with olive oil. 2) Add tomato sauce and warm. 3) Add spinach until wilted. 4) Toss with pasta and top with optional parmesan.', 560, 22, 90, 12, 1, '2025-12-29 16:59:57'),
(22, 'Chia Pudding Mango', 'Chia seeds 30 g; Milk 250 ml; Mango 150 g; Vanilla extract 1/4 tsp', '1) Mix chia seeds, milk, and vanilla. 2) Refrigerate 3 hours or overnight. 3) Top with mango before serving.', 380, 12, 48, 16, 1, '2025-12-29 16:59:57'),
(23, 'Beef and Veggie Stir-Fry (Lean)', 'Lean beef strips 150 g; Bell pepper 150 g; Onion 80 g; Broccoli 150 g; Low-sodium soy sauce 1 tbsp; Olive oil 1 tsp (5 ml); Black pepper 1/4 tsp', '1) Sear beef quickly. 2) Add vegetables with splash of water. 3) Add soy sauce and pepper. 4) Cook until vegetables are crisp-tender.', 520, 42, 28, 24, 1, '2025-12-29 16:59:57'),
(24, 'Sardine Lemon Rice', 'Sardines (canned in water) 120 g drained; Cooked rice 180 g; Spinach 1 cup (30 g); Lemon juice 1 tbsp; Black pepper 1/4 tsp', '1) Warm rice. 2) Add spinach to wilt. 3) Top with sardines. 4) Add lemon and pepper and serve.', 540, 32, 78, 12, 1, '2025-12-29 16:59:57'),
(25, 'Egg White Scramble Bowl', 'Egg whites 200 g; Whole egg 1 (50 g); Mushroom 150 g; Spinach 1 cup (30 g); Olive oil 1 tsp (5 ml); Black pepper 1/4 tsp', '1) Saute mushrooms with oil. 2) Add spinach to wilt. 3) Add egg whites and whole egg. 4) Scramble until set and serve.', 320, 36, 10, 12, 1, '2025-12-29 16:59:57'),
(26, 'Quinoa Chickpea Power Bowl', 'Cooked quinoa 170 g; Chickpeas cooked 180 g; Cucumber 120 g; Tomato 120 g; Olive oil 1 tbsp (15 ml); Lemon juice 1 tbsp; Black pepper 1/4 tsp', '1) Combine quinoa and chickpeas. 2) Add chopped cucumber and tomato. 3) Mix olive oil and lemon. 4) Toss and serve.', 650, 24, 92, 20, 1, '2025-12-29 16:59:57'),
(27, 'Tofu Egg Drop Soup', 'Firm tofu 150 g; Egg 1 (50 g); Vegetable broth 600 ml; Spinach 60 g; Ginger 1 tsp; Low-sodium soy sauce 1 tsp; Black pepper 1/4 tsp', '1) Heat broth with ginger and soy sauce. 2) Add tofu cubes and spinach. 3) Pour beaten egg slowly while stirring. 4) Season and serve.', 260, 20, 12, 12, 1, '2025-12-29 16:59:57'),
(28, 'Oven Roasted Veggie Tray', 'Broccoli 200 g; Carrot 150 g; Zucchini 200 g; Onion 100 g; Olive oil 1 tbsp (15 ml); Garlic powder 1/2 tsp; Black pepper 1/4 tsp', '1) Preheat oven to 200C. 2) Toss vegetables with oil and seasoning. 3) Roast 18 to 22 minutes. 4) Serve as a side or over rice.', 320, 8, 40, 14, 1, '2025-12-29 16:59:57'),
(29, 'Tuna Bean Salad', 'Tuna (in water) 120 g drained; White beans cooked 200 g; Red onion 40 g; Tomato 150 g; Olive oil 1 tbsp (15 ml); Lemon juice 1 tbsp; Black pepper 1/4 tsp', '1) Combine tuna and beans. 2) Add chopped onion and tomato. 3) Toss with olive oil, lemon, and pepper. 4) Chill 10 minutes.', 520, 42, 52, 14, 1, '2025-12-29 16:59:57'),
(30, 'Chicken Oat Congee', 'Rolled oats 50 g; Water 450 ml; Chicken breast 120 g; Ginger 1 tsp; Green onion 10 g; Soy sauce 1 tsp (optional)', '1) Simmer oats in water until thick. 2) Add shredded cooked chicken and ginger. 3) Cook 2 minutes. 4) Top with green onion and optional soy sauce.', 420, 36, 46, 10, 1, '2025-12-29 16:59:57');

--
-- 转储表的索引
--

--
-- 表的索引 `diet_goals`
--
ALTER TABLE `diet_goals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- 表的索引 `diet_logs`
--
ALTER TABLE `diet_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `log_date` (`log_date`),
  ADD KEY `food_id` (`food_id`);

--
-- 表的索引 `food_items`
--
ALTER TABLE `food_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `name` (`name`);

--
-- 表的索引 `meal_recipes`
--
ALTER TABLE `meal_recipes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recipe_name` (`recipe_name`),
  ADD KEY `is_healthy` (`is_healthy`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `diet_goals`
--
ALTER TABLE `diet_goals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `diet_logs`
--
ALTER TABLE `diet_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- 使用表AUTO_INCREMENT `food_items`
--
ALTER TABLE `food_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- 使用表AUTO_INCREMENT `meal_recipes`
--
ALTER TABLE `meal_recipes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
