# Training System Module

## Overview
This is a complete training system module that implements a sustainable workout planning system with automatic adjustments based on user adherence and performance metrics.

## Installation Steps

### 1. Database Setup

#### Option A: Using phpMyAdmin
1. Open phpMyAdmin at `http://localhost/phpmyadmin`
2. Select database `mysihat`
3. Go to "Import" tab
4. Import `training/sql/schema.sql` first
5. Then import `training/sql/seed.sql`

#### Option B: Using Command Line
```bash
# Navigate to project directory
cd E:\XAMPP\htdocs\mysihat

# Import schema
mysql -u root -p mysihat < training/sql/schema.sql

# Import seed data
mysql -u root -p mysihat < training/sql/seed.sql
```

### 2. Verify Installation
- Check that new columns exist in `users` table: `equipment_level`, `default_days_per_week`, `default_session_duration`, `prefer_home`
- Check that new tables exist: `exercises`, `plans`, `plan_days`, `plan_items`, `workouts`, `workout_items`
- Verify that at least 20 exercises are inserted in `exercises` table

### 3. Access the Module
1. Start XAMPP (Apache + MySQL)
2. Open browser: `http://localhost/mysihat/`
3. Login (or register if needed)
4. On Dashboard, click "Open Training System" button
5. Or navigate directly to: `http://localhost/mysihat/training/plan.php`

## Module Structure

```
training/
├── index.php              # Redirects to plan.php
├── plan.php               # Plan Builder page
├── today.php              # Today's workout execution
├── history.php            # Workout history
├── insight.php            # Metrics and recommendations
├── includes/
│   ├── header.php         # Module header
│   ├── nav.php            # Module navigation
│   └── footer.php         # Module footer
├── lib/
│   ├── db.php             # Database connection
│   ├── auth.php           # Authentication helper
│   ├── templates.php      # Training templates
│   ├── plan_service.php   # Plan generation service
│   ├── metrics_service.php # Metrics calculation
│   └── recommendation_service.php # Recommendation engine
├── api/
│   ├── update_item.php    # AJAX: Update workout item
│   └── finish_workout.php # AJAX: Finish workout
└── sql/
    ├── schema.sql         # Database schema
    └── seed.sql           # Seed data
```

## Demo Flow

### Step 1: Generate Plan
1. Go to `/training/plan.php`
2. Select:
   - Days per week: 4
   - Session duration: 45 minutes
   - Equipment: Home
   - Check "Prefer home workouts"
3. Click "Generate Plan"
4. View weekly plan with exercises

### Step 2: Start Today's Workout
1. Click "Start Today's Workout" on today's date
2. Or go to `/training/today.php`
3. Click "Start Workout"
4. Mark exercises as completed/uncompleted
5. Adjust RPE slider (1-10)
6. Optionally expand "Details" to enter sets/reps/weight
7. Click "Finish Workout" when done

### Step 3: View History
1. Go to `/training/history.php`
2. View list of past workouts
3. Click "View" to see workout details

### Step 4: Get Recommendations
1. Go to `/training/insight.php`
2. View weekly metrics:
   - Planned vs Completed sessions
   - Adherence rate
   - Average RPE
   - Miss streak
   - High RPE streak
3. Review recommendations (if available)
4. Click "Apply Recommendation" to generate next week plan with adjustments

### Step 5: Demonstrate Auto-Adjustment
1. Intentionally mark 2 workouts as incomplete (miss streak = 2)
2. Go to Insight page
3. System recommends:
   - Reduce days per week (4 → 3)
   - Reduce duration (45 → 30 min)
   - Switch to home mode
4. Click "Apply Recommendation"
5. View next week plan (automatically adjusted)

## Key Features

### 1. Plan Builder
- Template-based plan generation
- Supports 3/4/5 days per week
- Supports 30/45/60 minute sessions
- Equipment compatibility (home/dumbbell/gym)
- Automatic exercise substitution based on equipment

### 2. Workout Logging
- Real-time workout timer
- Simple completion tracking (checkbox)
- RPE (Rate of Perceived Exertion) slider (1-10)
- Optional detailed logging (sets/reps/weight)
- Auto-save via AJAX (debounced)

### 3. Metrics Engine
- Session completion rate
- Weekly adherence rate
- Average RPE
- Miss streak (consecutive missed sessions)
- High RPE streak (consecutive high-intensity sessions)
- Duration ratio (actual vs planned)

### 4. Recommendation Engine
Implements 6 rules:
- **R1**: Miss streak ≥ 2 → Downgrade plan
- **R2**: Adherence < 60% → Force 30min mode
- **R3**: High RPE streak ≥ 2 → Reduce sets, add rest
- **R4**: High adherence + moderate RPE → Slight increase
- **R5**: Frequent overtime → Simplify structure
- **R6**: Prefer home or miss streak → Switch to home

### 5. Automatic Plan Adjustment
- One-click apply recommendations
- Generates next week plan with adjusted parameters
- Maintains user preferences for future plans

## Technical Details

### Timezone
- All date/time calculations use `Asia/Kuala_Lumpur` timezone
- Week starts on Monday

### Database
- Uses PDO with prepared statements
- All queries are parameterized to prevent SQL injection
- Foreign keys with CASCADE/SET NULL as appropriate

### API Endpoints
- `/training/api/update_item.php` - Update workout item (POST JSON)
- `/training/api/finish_workout.php` - Finish workout (POST JSON)

### JavaScript
- Real-time workout timer
- Debounced auto-save (500ms)
- RPE slider updates
- Completion count updates
- Finish workout with summary modal

## Troubleshooting

### Database Connection Error
- Check XAMPP MySQL is running
- Verify database `mysihat` exists
- Check `config/db.php` credentials

### Tables Not Found
- Run `training/sql/schema.sql` first
- Then run `training/sql/seed.sql`

### No Exercises Available
- Check `exercises` table has data
- Re-run `training/sql/seed.sql`

### Plan Generation Fails
- Check user_id exists in `users` table
- Verify exercises table has entries for required patterns
- Check error logs in XAMPP Apache error log

### AJAX Not Working
- Check browser console for errors
- Verify `/mysihat/training/api/` paths are correct
- Ensure `assets/js/training.js` is loaded

## Notes
- Demo mode uses `user_id = 1` if session not available
- All dates use natural week (Monday-Sunday)
- Templates support full-body splits only (for consistency goal)
- Exercise substitution follows alt_exercise_id chain

