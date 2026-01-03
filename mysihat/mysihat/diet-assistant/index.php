<?php
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Diet Assistant</title>

  <link rel="stylesheet" href="assets/css/app.css?v=20251231_ui1">

  <style>
    .tab-button { padding: 10px; cursor: pointer; }
    .tab-content { display: none; padding: 20px; border: 1px solid #ccc; margin-top: 10px; }
    .active { display: block; }
    .row { display: flex; gap: 12px; flex-wrap: wrap; align-items: end; }
    .field { display: flex; flex-direction: column; gap: 6px; }
    input, select, button { padding: 8px; }
    .card { border: 1px solid #ddd; padding: 12px; margin-top: 12px; }
    .muted { color: #666; }
    ul { padding-left: 18px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
  </style>
</head>
<body>
  <h1>Diet Assistant</h1>

  <div>
    <button class="tab-button" onclick="showTab('goal')">Goal Settings</button>
    <button class="tab-button" onclick="showTab('dietRecord')">Diet Record</button>
    <button class="tab-button" onclick="showTab('report')">Nutrition Report</button>
    <button class="tab-button" onclick="showTab('mealRecommendation')">Meal Recommendation</button>
  </div>

  <div id="goal" class="tab-content active">
    <h2>Goal Settings</h2>
    <label>Goal Type</label><br/>
    <input id="goal_type" placeholder="cut / bulk / maintain" /><br/><br/>
    <label>Daily Calorie Target</label><br/>
    <input id="daily_calorie_target" type="number" min="0" step="1" /><br/><br/>
    <label>Protein Target (g)</label><br/>
    <input id="protein_target_g" type="number" min="0" step="1" /><br/><br/>
    <label>Carbs Target (g)</label><br/>
    <input id="carbs_target_g" type="number" min="0" step="1" /><br/><br/>
    <label>Fat Target (g)</label><br/>
    <input id="fat_target_g" type="number" min="0" step="1" /><br/><br/>
    <button id="saveBtn" type="button">Save Goal</button>
    <p id="status"></p>
    <h3>Current goal (debug)</h3>
    <pre id="output"></pre>
  </div>

  <div id="dietRecord" class="tab-content">
    <h2>Food Database and Diet Logs</h2>

    <div class="card">
      <h3>Food Database</h3>
      <label>Name:</label><br/>
      <input id="name" type="text" placeholder="Food Name"><br/><br/>
      <label>Calories:</label><br/>
      <input id="calories" type="number" min="0" step="1" placeholder="Calories"><br/><br/>
      <label>Protein (g):</label><br/>
      <input id="protein_g" type="number" min="0" step="0.1" placeholder="Protein (g)"><br/><br/>
      <label>Carbs (g):</label><br/>
      <input id="carbs_g" type="number" min="0" step="0.1" placeholder="Carbs (g)"><br/><br/>
      <label>Fat (g):</label><br/>
      <input id="fat_g" type="number" min="0" step="0.1" placeholder="Fat (g)"><br/><br/>
      <button id="addFoodBtn" type="button">Add Food</button>

      <div id="foodMsg" class="muted"></div>

      <h3>Search Food</h3>
      <div class="row">
        <div class="field">
          <input id="foodSearchQuery" type="text" placeholder="Search Food">
        </div>
        <button id="searchFoodBtn" type="button">Search</button>
      </div>

      <div id="foodSearchResults" class="card" style="display:none;"></div>
    </div>

    <div class="card">
      <h3>Diet Logs</h3>
      <div class="row">
        <div class="field">
          <label for="logDate">Date</label>
          <input id="logDate" type="date">
        </div>
        <div class="field" style="min-width: 240px;">
          <label for="logFood">Food</label>
          <input id="logFood" list="foodDatalist">
          <datalist id="foodDatalist"></datalist>
        </div>
        <div class="field">
          <label for="logGrams">Quantity</label>
          <input id="logGrams" type="number" min="1" step="1" value="1">
        </div>
        <div class="field">
          <button id="addLogBtn" type="button">Add To Day</button>
        </div>
      </div>
      <p class="muted" id="logsHint"></p>
    </div>

    <div class="card">
      <h3>Daily Totals</h3>
      <pre id="dayTotals"></pre>
    </div>

    <div class="card">
      <h3>Entries</h3>
      <ul id="logList"></ul>
    </div>
  </div>

  <div id="report" class="tab-content">
    <h2>Nutrition Report and Analysis</h2>
    <div class="card">
      <div class="row">
        <div class="field">
          <label for="reportStart">Start</label>
          <input id="reportStart" type="date">
        </div>
        <div class="field">
          <label for="reportEnd">End</label>
          <input id="reportEnd" type="date">
        </div>
        <div class="field">
          <button id="generateReportBtn" type="button">Generate Report</button>
        </div>
      </div>
      <p class="muted" id="reportHint"></p>
    </div>

    <div class="card">
      <h3>Summary</h3>
      <pre id="reportSummary"></pre>
    </div>

    <div class="card">
      <h3>Daily Breakdown</h3>
      <div id="reportDailyTable"></div>
    </div>

    <div class="card">
      <h3>Top Foods</h3>
      <div id="reportTopFoods"></div>
    </div>

    <div class="card">
      <h3>Diet Advice</h3>
      <pre id="dietAdvice"></pre>
      <button id="generateAdviceBtn" type="button">Generate Diet Advice</button>
    </div>
  </div>

  <div id="mealRecommendation" class="tab-content">
    <h2>Healthy Meal Recommendations</h2>
    <div class="card">
      <h3>Enter Ingredients</h3>
      <input id="mealIngredients" type="text" placeholder="Enter ingredients separated by commas (e.g. egg, spinach)">
      <button id="mealSearchBtn" type="button">Search Recipes</button>
      <div id="mealStatus" class="muted" style="margin-top:8px;"></div>
    </div>

    <div class="card">
      <h3>Recipe Results</h3>
      <ul id="mealResults"></ul>
    </div>
  </div>

  <script>
    function showTab(tabName) {
      const tabs = document.querySelectorAll('.tab-content');
      tabs.forEach(tab => tab.classList.remove('active'));
      const activeTab = document.getElementById(tabName);
      if (activeTab) activeTab.classList.add('active');
    }
  </script>

  <script src="assets/js/goal.js?v=20251231_goal1" defer></script>
  <script src="assets/js/food.js?v=20251231_add1" defer></script>
  <script src="assets/js/logs.js?v=20251231_logs1" defer></script>
  <script src="assets/js/report.js?v=debug_20251231_01" defer></script>
  <script src="assets/js/advice.js?v=20251231_adv2" defer></script>
  <script src="assets/js/meal.js?v=ignore_meal_external_20251231_1" defer></script>

  <script>
    (function () {
      function esc(s) {
        return String(s == null ? "" : s)
          .replace(/&/g, "&amp;")
          .replace(/</g, "&lt;")
          .replace(/>/g, "&gt;")
          .replace(/"/g, "&quot;")
          .replace(/'/g, "&#039;");
      }

      function normalize(data) {
        if (Array.isArray(data)) return data;
        if (data && Array.isArray(data.recipes)) return data.recipes;
        if (data && Array.isArray(data.data)) return data.data;
        return [];
      }

      function setStatus(msg) {
        var s = document.getElementById("mealStatus");
        if (s) s.textContent = msg == null ? "" : String(msg);
      }

      function render(recipes) {
        var ul = document.getElementById("mealResults");
        if (!ul) return;

        ul.innerHTML = "";

        if (!recipes || recipes.length === 0) {
          ul.innerHTML = "<li>No matching recipes found.</li>";
          return;
        }

        for (var i = 0; i < recipes.length; i++) {
          var r = recipes[i] || {};
          var name = r.name || "";
          var ingredients = Array.isArray(r.ingredients) ? r.ingredients.join(", ") : String(r.ingredients == null ? "" : r.ingredients);
          var instructions = r.instructions || "";
          var calories = r.calories == null ? "" : r.calories;
          var protein = r.protein_g == null ? "" : r.protein_g;
          var carbs = r.carbs_g == null ? "" : r.carbs_g;
          var fat = r.fat_g == null ? "" : r.fat_g;

          var html =
            "<div class=\"card\">" +
            "<div><strong>" + esc(name) + "</strong></div>" +
            "<div style=\"margin-top:8px;\"><strong>Ingredients:</strong> " + esc(ingredients) + "</div>" +
            "<div style=\"margin-top:8px;\"><strong>Instructions:</strong> " + esc(instructions) + "</div>" +
            "<div style=\"margin-top:8px;\" class=\"muted\">" +
            "Calories: " + esc(calories) +
            " | Protein(g): " + esc(protein) +
            " | Carbs(g): " + esc(carbs) +
            " | Fat(g): " + esc(fat) +
            "</div>" +
            "</div>";

          var li = document.createElement("li");
          li.innerHTML = html;
          ul.appendChild(li);
        }
      }

      async function fetchJSON(url) {
        var res = await fetch(url, { cache: "no-store" });
        var txt = await res.text();
        var data = null;
        try { data = JSON.parse(txt); } catch (e) { data = null; }
        return { ok: res.ok, status: res.status, data: data, raw: txt };
      }

      async function search(btn) {
        var input = document.getElementById("mealIngredients");
        if (!input) return;

        var q = String(input.value || "").trim();
        if (!q) {
          setStatus("Please enter ingredients.");
          render([]);
          return;
        }

        btn.disabled = true;
        var prev = btn.textContent;
        btn.textContent = "Searching...";
        setStatus("Requesting...");

        try {
          var url = "api/meal-recommendation.php?ingredients=" + encodeURIComponent(q) + "&_=" + Date.now();
          var resp = await fetchJSON(url);

          if (!resp.ok) {
            setStatus("Request failed. HTTP " + resp.status);
            render([]);
            return;
          }

          var recipes = normalize(resp.data);
          setStatus("Done. Found " + recipes.length + " recipe(s).");
          render(recipes);
        } catch (e) {
          setStatus("Error: " + (e && e.message ? e.message : "Unknown error"));
          render([]);
        } finally {
          btn.textContent = prev || "Search Recipes";
          btn.disabled = false;
        }
      }

      var btn = document.getElementById("mealSearchBtn");
      if (btn) {
        setStatus("Ready.");
        btn.addEventListener("click", function (e) {
          e.preventDefault();
          search(btn);
        }, true);
      }
    })();
  </script>
</body>
</html>
