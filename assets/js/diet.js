(function () {
  const USER_ID = 1;

  function setText(id, text) {
    const el = document.getElementById(id);
    if (el) el.textContent = text == null ? "" : String(text);
  }

  function getVal(id) {
    const el = document.getElementById(id);
    return el ? String(el.value || "").trim() : "";
  }

  function getNum(id) {
    const el = document.getElementById(id);
    const v = el ? el.value : "";
    const n = Number(v);
    return Number.isFinite(n) ? n : 0;
  }

  async function fetchJSON(url, options) {
    const res = await fetch(url, options);
    const txt = await res.text();
    let data = null;
    try { data = JSON.parse(txt); } catch (e) { data = null; }
    return { ok: res.ok, status: res.status, data, raw: txt };
  }

  async function loadGoal() {
    const r = await fetchJSON("/mysihat/pages/goal_get.php?user_id=" + encodeURIComponent(USER_ID) + "&_=" + Date.now(), { cache: "no-store" });
    if (!r.ok || !r.data || !r.data.success) {
      setText("output", r.data && r.data.message ? r.data.message : "Failed to load goal");
      return;
    }
    const g = r.data.data || {};
    const elType = document.getElementById("goal_type");
    const elCal = document.getElementById("daily_calorie_target");
    const elP = document.getElementById("protein_target_g");
    const elC = document.getElementById("carbs_target_g");
    const elF = document.getElementById("fat_target_g");
    if (elType) elType.value = g.goal_type || "";
    if (elCal) elCal.value = g.daily_calorie_target || 0;
    if (elP) elP.value = g.protein_target_g || 0;
    if (elC) elC.value = g.carbs_target_g || 0;
    if (elF) elF.value = g.fat_target_g || 0;
    setText("output", JSON.stringify(g, null, 2));
  }

  async function saveGoal() {
    const payload = {
      user_id: USER_ID,
      goal_type: getVal("goal_type"),
      daily_calorie_target: getNum("daily_calorie_target"),
      protein_target_g: getNum("protein_target_g"),
      carbs_target_g: getNum("carbs_target_g"),
      fat_target_g: getNum("fat_target_g")
    };

    setText("status", "Saving...");
    const r = await fetchJSON("/mysihat/pages/goal_set.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    });

    if (!r.ok || !r.data || !r.data.success) {
      setText("status", (r.data && r.data.message) ? r.data.message : ("Save failed. HTTP " + r.status));
      return;
    }

    setText("status", "Saved.");
    await loadGoal();
  }

  function initGoal() {
    const btn = document.getElementById("saveBtn");
    if (btn) btn.addEventListener("click", function (e) { e.preventDefault(); saveGoal(); }, true);
    loadGoal();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initGoal);
  } else {
    initGoal();
  }
})();

document.addEventListener("DOMContentLoaded", () => {
  const API_ADD = "/mysihat/pages/food_add.php";
  const API_GET = "/mysihat/pages/food_get.php";

  const elName = document.getElementById("name");
  const elCalories = document.getElementById("calories");
  const elProtein = document.getElementById("protein_g");
  const elCarbs = document.getElementById("carbs_g");
  const elFat = document.getElementById("fat_g");
  const elAddBtn = document.getElementById("addFoodBtn");
  const elMsg = document.getElementById("foodMsg");
  const elQuery = document.getElementById("foodSearchQuery");
  const elSearchBtn = document.getElementById("searchFoodBtn");
  const elResults = document.getElementById("foodSearchResults");

  function setMsg(text) {
    if (elMsg) elMsg.textContent = text || "";
  }

  function numVal(el, fallback = 0) {
    if (!el) return fallback;
    const n = Number(el.value);
    return Number.isFinite(n) ? n : fallback;
  }

  function strVal(el) {
    return el ? String(el.value || "").trim() : "";
  }

  async function fetchJSON(url, options) {
    const res = await fetch(url, options);
    const txt = await res.text();
    let data = null;
    try { data = JSON.parse(txt); } catch (e) { data = null; }
    return { ok: res.ok, status: res.status, data, raw: txt };
  }

  async function addFood() {
    const payload = {
      name: strVal(elName),
      calories: numVal(elCalories, 0),
      protein_g: numVal(elProtein, 0),
      carbs_g: numVal(elCarbs, 0),
      fat_g: numVal(elFat, 0)
    };

    if (!payload.name) {
      setMsg("Name is required.");
      return;
    }

    setMsg("Adding...");
    const r = await fetchJSON(API_ADD, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    });

    if (!r.ok || !r.data || !r.data.success) {
      setMsg((r.data && r.data.message) ? r.data.message : ("Add failed. HTTP " + r.status));
      return;
    }

    setMsg("Added.");
    if (elName) elName.value = "";
    if (elCalories) elCalories.value = "";
    if (elProtein) elProtein.value = "";
    if (elCarbs) elCarbs.value = "";
    if (elFat) elFat.value = "";
  }

  function normalizeFoods(data) {
    if (!data) return [];
    if (Array.isArray(data.foods)) return data.foods;
    if (Array.isArray(data.data)) return data.data;
    if (Array.isArray(data)) return data;
    return [];
  }

  async function searchFood() {
    const q = strVal(elQuery);
    const url = API_GET + "?q=" + encodeURIComponent(q) + "&_=" + Date.now();
    const r = await fetchJSON(url, { cache: "no-store" });

    if (!r.ok || !r.data || !r.data.success) {
      if (elResults) {
        elResults.style.display = "block";
        elResults.textContent = (r.data && r.data.message) ? r.data.message : ("Search failed. HTTP " + r.status);
      }
      return;
    }

    const foods = normalizeFoods(r.data);
    if (!elResults) return;

    elResults.style.display = "block";

    if (!foods.length) {
      elResults.innerHTML = "<div class=\"muted\">No results.</div>";
      return;
    }

    let html = "<table><thead><tr><th>Name</th><th>Calories</th><th>Protein</th><th>Carbs</th><th>Fat</th></tr></thead><tbody>";
    for (const f of foods) {
      const name = f.name || f.food_name || "";
      const calories = f.calories ?? f.food_calories ?? "";
      const protein = f.protein_g ?? f.food_protein ?? "";
      const carbs = f.carbs_g ?? f.food_carbs ?? "";
      const fat = f.fat_g ?? f.food_fat ?? "";
      html += "<tr><td>" + escapeHtml(String(name)) + "</td><td>" + escapeHtml(String(calories)) + "</td><td>" + escapeHtml(String(protein)) + "</td><td>" + escapeHtml(String(carbs)) + "</td><td>" + escapeHtml(String(fat)) + "</td></tr>";
    }
    html += "</tbody></table>";
    elResults.innerHTML = html;
  }

  function escapeHtml(s) {
    return String(s == null ? "" : s)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  if (elAddBtn) elAddBtn.addEventListener("click", (e) => { e.preventDefault(); addFood(); }, true);
  if (elSearchBtn) elSearchBtn.addEventListener("click", (e) => { e.preventDefault(); searchFood(); }, true);
});

(function () {
  const API_BASE = "/mysihat/pages";
  const USER_ID = 1;

  const elDate = document.getElementById("logDate");
  const elFood = document.getElementById("logFood");
  const elFoodList = document.getElementById("foodDatalist");
  const elQty = document.getElementById("logGrams");
  const elAdd = document.getElementById("addLogBtn");
  const elList = document.getElementById("logList");
  const elTotals = document.getElementById("dayTotals");
  const elHint = document.getElementById("logsHint");

  function todayISO() {
    const d = new Date();
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");
    return y + "-" + m + "-" + day;
  }

  function setText(el, text) {
    if (el) el.textContent = text == null ? "" : String(text);
  }

  function esc(s) {
    return String(s == null ? "" : s)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  async function fetchJSON(url, options) {
    const res = await fetch(url, options);
    const txt = await res.text();
    let data = null;
    try { data = JSON.parse(txt); } catch (e) { data = null; }
    return { ok: res.ok, status: res.status, data, raw: txt };
  }

  function normalizeFoods(data) {
    if (!data) return [];
    if (Array.isArray(data.foods)) return data.foods;
    if (Array.isArray(data.data)) return data.data;
    if (Array.isArray(data)) return data;
    return [];
  }

  async function loadFoodsToDatalist() {
    if (!elFoodList) return;
    const r = await fetchJSON(API_BASE + "/food_get.php?_=" + Date.now(), { cache: "no-store" });
    if (!r.ok || !r.data || !r.data.success) return;
    const foods = normalizeFoods(r.data);
    elFoodList.innerHTML = "";
    for (const f of foods) {
      const name = f.name || f.food_name || "";
      const opt = document.createElement("option");
      opt.value = name;
      elFoodList.appendChild(opt);
    }
  }

  function normalizeLogs(data) {
    if (!data) return [];
    if (Array.isArray(data.logs)) return data.logs;
    if (Array.isArray(data.data)) return data.data;
    if (Array.isArray(data)) return data;
    return [];
  }

  async function loadDay(dateStr) {
    const url = API_BASE + "/logs_get.php?user_id=" + encodeURIComponent(USER_ID) + "&log_date=" + encodeURIComponent(dateStr) + "&_=" + Date.now();
    const r = await fetchJSON(url, { cache: "no-store" });

    if (!r.ok || !r.data || !r.data.success) {
      setText(elHint, (r.data && r.data.message) ? r.data.message : ("Load logs failed. HTTP " + r.status));
      if (elList) elList.innerHTML = "";
      setText(elTotals, "");
      return;
    }

    setText(elHint, "");
    const rows = normalizeLogs(r.data);
    renderLogs(rows);
    renderTotals(rows);
  }

  function renderTotals(rows) {
    let calories = 0, protein = 0, carbs = 0, fat = 0;

    for (const r of rows) {
      calories += Number(r.total_calories ?? r.calories ?? 0);
      protein += Number(r.total_protein_g ?? r.protein_g ?? 0);
      carbs += Number(r.total_carbs_g ?? r.carbs_g ?? 0);
      fat += Number(r.total_fat_g ?? r.fat_g ?? 0);
    }

    const obj = {
      calories: Math.round(calories),
      protein_g: Math.round(protein * 10) / 10,
      carbs_g: Math.round(carbs * 10) / 10,
      fat_g: Math.round(fat * 10) / 10
    };

    setText(elTotals, JSON.stringify(obj, null, 2));
  }

  function renderLogs(rows) {
    if (!elList) return;
    elList.innerHTML = "";

    if (!rows.length) {
      elList.innerHTML = "<li class=\"muted\">No entries.</li>";
      return;
    }

    for (const r of rows) {
      const li = document.createElement("li");
      const name = r.name || r.food_name || "";
      const qty = r.quantity ?? r.grams ?? r.qty ?? r.servings ?? 1;
      const cals = r.total_calories ?? r.calories ?? 0;

      const id = (r && (r.id ?? r.log_id ?? r.diet_log_id ?? r.logId ?? r.logID)) ?? null;

      const btn = document.createElement("button");
      btn.type = "button";
      btn.textContent = "Delete";
      btn.addEventListener("click", function (e) {
        e.preventDefault();
        if (!id) {
          setText(elHint, "Missing id.");
          return;
        }
        deleteLog(id);
      }, true);

      li.innerHTML = "<span><strong>" + esc(name) + "</strong> | Qty: " + esc(qty) + " | Calories: " + esc(cals) + "</span> ";
      li.appendChild(btn);
      elList.appendChild(li);
    }
  }

  async function addLog(dateStr, foodName, qty) {
    const payload = { user_id: USER_ID, log_date: dateStr, food_name: foodName, quantity: qty };
    const r = await fetchJSON(API_BASE + "/logs_add.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    });

    if (!r.ok || !r.data || !r.data.success) {
      setText(elHint, (r.data && r.data.message) ? r.data.message : ("Add failed. HTTP " + r.status));
      return false;
    }

    setText(elHint, "");
    return true;
  }

  async function deleteLog(id) {
    const url =
      API_BASE +
      "/logs_delete.php?user_id=" +
      encodeURIComponent(USER_ID) +
      "&id=" +
      encodeURIComponent(id) +
      "&_=" +
      Date.now();

    const r = await fetchJSON(url, { cache: "no-store" });

    if (!r.ok || !r.data || !r.data.success) {
      setText(elHint, (r.data && r.data.message) ? r.data.message : ("Delete failed. HTTP " + r.status));
      return;
    }

    const dateStr = elDate && elDate.value ? elDate.value : todayISO();
    loadDay(dateStr);
  }

  function init() {
    if (elDate && !elDate.value) elDate.value = todayISO();

    loadFoodsToDatalist();

    if (elDate) {
      elDate.addEventListener("change", function () {
        const dateStr = elDate.value || todayISO();
        loadDay(dateStr);
      }, true);
    }

    if (elAdd) {
      elAdd.addEventListener("click", async function (e) {
        e.preventDefault();
        const dateStr = elDate && elDate.value ? elDate.value : todayISO();
        const foodName = elFood ? String(elFood.value || "").trim() : "";
        const qty = elQty ? Number(elQty.value || 1) : 1;

        if (!foodName) {
          setText(elHint, "Food is required.");
          return;
        }
        if (!Number.isFinite(qty) || qty <= 0) {
          setText(elHint, "Quantity must be a positive number.");
          return;
        }

        setText(elHint, "Saving...");
        const ok = await addLog(dateStr, foodName, qty);
        if (ok) loadDay(dateStr);
      }, true);
    }

    const dateStr = elDate && elDate.value ? elDate.value : todayISO();
    loadDay(dateStr);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();

(function () {
  const API_BASE = "/mysihat/pages";
  const USER_ID = 1;

  const elStart = document.getElementById("reportStart");
  const elEnd = document.getElementById("reportEnd");
  const elBtn = document.getElementById("generateReportBtn");
  const elHint = document.getElementById("reportHint");
  const elSummary = document.getElementById("reportSummary");
  const elDaily = document.getElementById("reportDailyTable");
  const elTop = document.getElementById("reportTopFoods");

  function setText(el, t) {
    if (el) el.textContent = t == null ? "" : String(t);
  }

  function esc(s) {
    return String(s == null ? "" : s)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function iso(d) {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");
    return y + "-" + m + "-" + day;
  }

  function defaultRange() {
    const end = new Date();
    const start = new Date();
    start.setDate(end.getDate() - 6);
    return { start: iso(start), end: iso(end) };
  }

  async function fetchJSON(url, options) {
    const res = await fetch(url, options);
    const txt = await res.text();
    let data = null;
    try { data = JSON.parse(txt); } catch (e) { data = null; }
    return { ok: res.ok, status: res.status, data, raw: txt };
  }

  function normalize(data) {
    if (!data) return {};
    return data.data || data;
  }

  function renderDaily(rows) {
    if (!elDaily) return;
    if (!Array.isArray(rows) || !rows.length) {
      elDaily.innerHTML = "<div class=\"muted\">No data.</div>";
      return;
    }

    let html = "<table><thead><tr><th>Date</th><th>Calories</th><th>Protein</th><th>Carbs</th><th>Fat</th></tr></thead><tbody>";
    for (const r of rows) {
      html += "<tr><td>" + esc(r.date) + "</td><td>" + esc(r.calories) + "</td><td>" + esc(r.protein_g) + "</td><td>" + esc(r.carbs_g) + "</td><td>" + esc(r.fat_g) + "</td></tr>";
    }
    html += "</tbody></table>";
    elDaily.innerHTML = html;
  }

  function renderTopFoods(rows) {
    if (!elTop) return;
    if (!Array.isArray(rows) || !rows.length) {
      elTop.innerHTML = "<div class=\"muted\">No data.</div>";
      return;
    }

    let html = "<ul>";
    for (const r of rows) {
      html += "<li><strong>" + esc(r.food_name || r.name || "") + "</strong> - " + esc(r.count || r.times || r.total || 0) + "</li>";
    }
    html += "</ul>";
    elTop.innerHTML = html;
  }

  async function generate() {
    const start = elStart ? String(elStart.value || "").trim() : "";
    const end = elEnd ? String(elEnd.value || "").trim() : "";

    if (!start || !end) {
      setText(elHint, "Start and End are required.");
      return;
    }

    if (elBtn) elBtn.disabled = true;
    setText(elHint, "Generating...");
    try {
      const url = API_BASE + "/report.php?user_id=" + encodeURIComponent(USER_ID) + "&start=" + encodeURIComponent(start) + "&end=" + encodeURIComponent(end) + "&_=" + Date.now();
      const r = await fetchJSON(url, { cache: "no-store" });

      if (!r.ok || !r.data || !r.data.success) {
        setText(elHint, (r.data && r.data.message) ? r.data.message : ("Report failed. HTTP " + r.status));
        return;
      }

      const d = normalize(r.data);
      setText(elHint, "");
      setText(elSummary, JSON.stringify(d.summary || {}, null, 2));
      renderDaily(d.daily || []);
      renderTopFoods(d.top_foods || []);
    } finally {
      if (elBtn) elBtn.disabled = false;
    }
  }

  function init() {
    const dr = defaultRange();
    if (elStart && !elStart.value) elStart.value = dr.start;
    if (elEnd && !elEnd.value) elEnd.value = dr.end;

    if (elBtn) elBtn.addEventListener("click", function (e) { e.preventDefault(); generate(); }, true);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();

(function () {
  const API_BASE = "/mysihat/pages";
  const USER_ID = 1;

  const elOut = document.getElementById("dietAdvice");
  const elBtn = document.getElementById("generateAdviceBtn");

  function setText(el, t) {
    if (el) el.textContent = t == null ? "" : String(t);
  }

  async function fetchJSON(url, options) {
    const res = await fetch(url, options);
    const txt = await res.text();
    let data = null;
    try { data = JSON.parse(txt); } catch (e) { data = null; }
    return { ok: res.ok, status: res.status, data, raw: txt };
  }

  async function generateAdvice() {
    if (elBtn) elBtn.disabled = true;
    setText(elOut, "Generating...");

    try {
      const url = API_BASE + "/advice_generate.php?user_id=" + encodeURIComponent(USER_ID) + "&_=" + Date.now();
      const r = await fetchJSON(url, { cache: "no-store" });

      if (!r.ok || !r.data || !r.data.success) {
        setText(elOut, (r.data && r.data.message) ? r.data.message : ("Advice failed. HTTP " + r.status));
        return;
      }

      setText(elOut, r.data.advice || r.data.data || "");
    } finally {
      if (elBtn) elBtn.disabled = false;
    }
  }

  function init() {
    if (elBtn) elBtn.addEventListener("click", function (e) { e.preventDefault(); generateAdvice(); }, true);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();

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
      var url = "/mysihat/pages/meal-recommendation.php?ingredients=" + encodeURIComponent(q) + "&_=" + Date.now();
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
