<?php
require_once __DIR__ . "/../auth/guard.php";
?>

<?php require_once __DIR__ . "/../partials/header.php"; ?>
<?php require_once __DIR__ . "/../partials/nav.php"; ?>

<link rel="stylesheet" href="../assets/css/diet-assistant.css?v=20260107">

<div class="container py-4" style="max-width: 1100px;">
  <h3 class="fw-bold mb-3">Diet</h3>

  <ul class="nav nav-pills gap-2 mb-3 diet-tabs" id="dietTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="tab-goal-btn" data-bs-toggle="pill" data-bs-target="#tab-goal" type="button" role="tab" aria-selected="true">Goal Settings</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="tab-record-btn" data-bs-toggle="pill" data-bs-target="#tab-record" type="button" role="tab">Diet Record</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="tab-report-btn" data-bs-toggle="pill" data-bs-target="#tab-report" type="button" role="tab">Nutrition Report</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="tab-meal-btn" data-bs-toggle="pill" data-bs-target="#tab-meal" type="button" role="tab">Meal Recommendation</button>
    </li>
  </ul>

  <div class="tab-content" id="dietTabsContent">
    <div class="tab-pane fade show active" id="tab-goal" role="tabpanel" tabindex="0">
      <div class="card shadow-sm diet-card">
        <div class="card-body">
          <h4 class="fw-bold mb-3">Goal Settings</h4>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Goal Type</label>
              <input id="goal_type" class="form-control" placeholder="cut / bulk / maintain" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Daily Calorie Target</label>
              <input id="daily_calorie_target" class="form-control" type="number" min="0" step="1" />
            </div>
            <div class="col-md-4">
              <label class="form-label">Protein Target (g)</label>
              <input id="protein_target_g" class="form-control" type="number" min="0" step="1" />
            </div>
            <div class="col-md-4">
              <label class="form-label">Carbs Target (g)</label>
              <input id="carbs_target_g" class="form-control" type="number" min="0" step="1" />
            </div>
            <div class="col-md-4">
              <label class="form-label">Fat Target (g)</label>
              <input id="fat_target_g" class="form-control" type="number" min="0" step="1" />
            </div>
            <div class="col-12 d-flex gap-2 align-items-center">
              <button id="saveBtn" type="button" class="btn btn-primary">Save Goal</button>
              <span id="status" class="text-muted"></span>
            </div>
          </div>

          <div class="mt-4">
           <h6 class="fw-bold mb-2">Current Goal</h6>
           <div id="output" class="goal-summary"></div>
          </div>
        </div>
      </div>
    </div>

    <div class="tab-pane fade" id="tab-record" role="tabpanel" tabindex="0">
      <div class="card shadow-sm diet-card mb-3">
        <div class="card-body">
          <h4 class="fw-bold mb-3">Food Catalog</h4>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Name</label>
              <input id="name" class="form-control" type="text" placeholder="Food Name">
            </div>
            <div class="col-md-6">
              <label class="form-label">Calories</label>
              <input id="calories" class="form-control" type="number" min="0" step="1" placeholder="Calories">
            </div>
            <div class="col-md-4">
              <label class="form-label">Protein (g)</label>
              <input id="protein_g" class="form-control" type="number" min="0" step="0.1" placeholder="Protein (g)">
            </div>
            <div class="col-md-4">
              <label class="form-label">Carbs (g)</label>
              <input id="carbs_g" class="form-control" type="number" min="0" step="0.1" placeholder="Carbs (g)">
            </div>
            <div class="col-md-4">
              <label class="form-label">Fat (g)</label>
              <input id="fat_g" class="form-control" type="number" min="0" step="0.1" placeholder="Fat (g)">
            </div>
            <div class="col-12 d-flex gap-2 align-items-center">
              <button id="addFoodBtn" type="button" class="btn btn-primary">Add Food</button>
              <span id="foodMsg" class="text-muted"></span>
            </div>
          </div>

          <div class="mt-4">
            <h6 class="fw-bold mb-2">Search Food</h6>
            <div class="row g-2 align-items-end">
              <div class="col-md-8">
                <input id="foodSearchQuery" class="form-control" type="text" placeholder="Search Food">
              </div>
              <div class="col-md-4 d-grid">
                <button id="searchFoodBtn" type="button" class="btn btn-outline-primary">Search</button>
              </div>
            </div>

            <div id="foodSearchResults" class="diet-surface mt-3" style="display:none;"></div>
          </div>
        </div>
      </div>

      <div class="card shadow-sm diet-card mb-3">
        <div class="card-body">
          <h4 class="fw-bold mb-3">Diet Logs</h4>

          <div class="row g-3 align-items-end">
            <div class="col-md-3">
              <label class="form-label">Date</label>
              <input id="logDate" class="form-control" type="date">
            </div>
            <div class="col-md-5">
              <label class="form-label">Food</label>
              <input id="logFood" class="form-control" list="foodDatalist">
              <datalist id="foodDatalist"></datalist>
            </div>
            <div class="col-md-2">
              <label class="form-label">Quantity</label>
              <input id="logGrams" class="form-control" type="number" min="1" step="1" value="1">
            </div>
            <div class="col-md-2 d-grid">
              <button id="addLogBtn" type="button" class="btn btn-primary">Add To Day</button>
            </div>
          </div>

          <p class="text-muted mt-2 mb-0" id="logsHint"></p>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-lg-5">
          <div class="card shadow-sm diet-card h-100">
            <div class="card-body">
             <h5 class="fw-bold mb-2">Daily Totals</h5>
             <div id="dayTotals" class="diet-surface"></div>
            </div>
          </div>
        </div>
        <div class="col-lg-7">
          <div class="card shadow-sm diet-card h-100">
            <div class="card-body">
              <h5 class="fw-bold mb-2">Entries</h5>
              <ul id="logList" class="mb-0"></ul>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="tab-pane fade" id="tab-report" role="tabpanel" tabindex="0">
      <div class="card shadow-sm diet-card mb-3">
        <div class="card-body">
          <h4 class="fw-bold mb-3">Nutrition Report and Analysis</h4>

          <div class="row g-3 align-items-end">
            <div class="col-md-4">
              <label class="form-label">Start</label>
              <input id="reportStart" class="form-control" type="date">
            </div>
            <div class="col-md-4">
              <label class="form-label">End</label>
              <input id="reportEnd" class="form-control" type="date">
            </div>
            <div class="col-md-4 d-grid">
              <button id="generateReportBtn" type="button" class="btn btn-primary">Generate Report</button>
            </div>
          </div>

          <p class="text-muted mt-2 mb-0" id="reportHint"></p>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-lg-6">
          <div class="card shadow-sm diet-card h-100">
            <div class="card-body">
             <h5 class="fw-bold mb-2">Summary</h5>
             <div id="reportSummary" class="diet-surface"></div>
            </div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="card shadow-sm diet-card h-100">
            <div class="card-body">
              <h5 class="fw-bold mb-2">Macro Ratio</h5>
              <div class="diet-chart-box"><canvas id="macroPieChart"></canvas></div>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-3 mt-3">
        <div class="col-lg-6">
          <div class="card shadow-sm diet-card h-100">
            <div class="card-body">
              <h5 class="fw-bold mb-2">Daily Breakdown</h5>
              <div id="reportDailyTable" class="diet-scroll"></div>
            </div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="card shadow-sm diet-card h-100">
            <div class="card-body">
              <h5 class="fw-bold mb-2">Daily Trend</h5>
              <div class="diet-chart-box"><canvas id="macroLineChart"></canvas></div>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-3 mt-3">
        <div class="col-lg-6">
          <div class="card shadow-sm diet-card h-100">
            <div class="card-body">
              <h5 class="fw-bold mb-2">Top Foods</h5>
              <div id="reportTopFoods" class="diet-scroll"></div>
            </div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="card shadow-sm diet-card h-100">
            <div class="card-body">
              <h5 class="fw-bold mb-2">Top Foods Chart</h5>
              <div class="diet-chart-box"><canvas id="topFoodsBarChart"></canvas></div>
            </div>
          </div>
        </div>
      </div>

      <div class="card shadow-sm diet-card mt-3">
        <div class="card-body">
          <h5 class="fw-bold mb-3">Diet Advice</h5>

          <div class="mb-3">
            <label class="form-label fw-bold">Select Your Dietary Goal</label>

            <div class="form-check">
              <input class="form-check-input" type="radio" name="diet_goal" value="Vegetarian">
              <label class="form-check-label">Vegetarian</label>
            </div>

            <div class="form-check">
              <input class="form-check-input" type="radio" name="diet_goal" value="Maintain Weight" checked>
              <label class="form-check-label">Maintain Weight</label>
            </div>

            <div class="form-check">
              <input class="form-check-input" type="radio" name="diet_goal" value="Weight Loss">
              <label class="form-check-label">Weight Loss</label>
            </div>

            <div class="form-check">
              <input class="form-check-input" type="radio" name="diet_goal" value="Muscle Gain">
              <label class="form-check-label">Muscle Gain</label>
            </div>
          </div>

          <pre id="dietAdvice" class="diet-pre mb-3"></pre>
          <button id="generateAdviceBtn" type="button" class="btn btn-outline-primary">Generate Diet Advice</button>
        </div>
      </div>
    </div>

    <div class="tab-pane fade" id="tab-meal" role="tabpanel" tabindex="0">
      <div class="card shadow-sm diet-card mb-3">
        <div class="card-body">
          <h4 class="fw-bold mb-3">Healthy Meal Recommendations</h4>

          <label class="form-label">Enter Ingredients</label>
          <div class="row g-2">
            <div class="col-md-9">
              <input id="mealIngredients" class="form-control" type="text" placeholder="Enter ingredients separated by commas (e.g. egg, spinach)">
            </div>
            <div class="col-md-3 d-grid">
              <button id="mealSearchBtn" type="button" class="btn btn-primary">Search Recipes</button>
            </div>
          </div>
          <div id="mealStatus" class="text-muted mt-2"></div>
        </div>
      </div>

      <div class="card shadow-sm diet-card">
        <div class="card-body">
          <h5 class="fw-bold mb-2">Recipe Results</h5>
          <ul id="mealResults" class="mb-0"></ul>
        </div>
      </div>
    </div>
  </div>

  <script src="../assets/js/chart.js?v=20260107" defer></script>
  <script src="../assets/js/diet.js?v=20260110" defer></script>
</div>
<?php require_once __DIR__ . "/../partials/footer.php"; ?>