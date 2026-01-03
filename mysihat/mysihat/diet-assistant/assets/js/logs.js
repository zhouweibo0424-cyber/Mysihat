(function () {
  const API_BASE = "/diet-assistant/api";
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
    return `${y}-${m}-${day}`;
  }

  function round1(n) {
    const x = Number(n || 0);
    return Math.round(x * 10) / 10;
  }

  function round0(n) {
    const x = Number(n || 0);
    return Math.round(x);
  }

  function activeDate() {
    return (elDate && elDate.value) || todayISO();
  }

  async function fetchJSON(url, options) {
    const res = await fetch(url, options);
    const text = await res.text();
    let data;
    try {
      data = JSON.parse(text);
    } catch {
      data = null;
    }
    if (!res.ok) {
      const msg = data && data.message ? data.message : text || "Request failed";
      throw new Error(msg);
    }
    return data;
  }

  function unwrapFoods(payload) {
    if (Array.isArray(payload)) return payload;
    if (payload && Array.isArray(payload.foods)) return payload.foods;
    if (payload && Array.isArray(payload.data)) return payload.data;
    if (payload && Array.isArray(payload.items)) return payload.items;
    return [];
  }

  function normalizeFoods(foodsRaw) {
    const foods = Array.isArray(foodsRaw) ? foodsRaw : [];
    return foods
      .map((f, idx) => {
        const id = String(f.id ?? f.food_id ?? idx + 1);
        const name = String(f.name ?? f.food_name ?? "").trim();
        const calories = Number(f.calories ?? f.food_calories ?? 0);
        const protein_g = Number(f.protein_g ?? f.food_protein ?? 0);
        const carbs_g = Number(f.carbs_g ?? f.food_carbs ?? 0);
        const fat_g = Number(f.fat_g ?? f.food_fat ?? 0);
        if (!name) return null;
        return { id, name, calories, protein_g, carbs_g, fat_g };
      })
      .filter(Boolean);
  }

  async function loadFoods() {
    const payload = await fetchJSON(`${API_BASE}/food_get.php`);
    return normalizeFoods(unwrapFoods(payload));
  }

  async function renderFoodOptions() {
    if (!elFoodList || !elFood || !elAdd || !elHint) return;

    elFoodList.innerHTML = "";

    let foods = [];
    try {
      foods = await loadFoods();
    } catch (e) {
      elFood.disabled = true;
      elAdd.disabled = true;
      elHint.textContent = String(e.message || "Failed to load foods");
      return;
    }

    if (foods.length === 0) {
      elFood.disabled = true;
      elAdd.disabled = true;
      elHint.textContent = "Add foods in Food Database first.";
      return;
    }

    foods.forEach((f) => {
      const opt = document.createElement("option");
      opt.value = f.name;
      opt.textContent = `${f.name} (per 100g: ${f.calories} kcal)`;
      elFoodList.appendChild(opt);
    });

    elFood.disabled = false;
    elAdd.disabled = false;
    elHint.textContent = "";
  }

  async function loadDay() {
    const date = activeDate();
    return fetchJSON(`${API_BASE}/logs_get.php?user_id=${encodeURIComponent(USER_ID)}&log_date=${encodeURIComponent(date)}`);
  }

  function renderTotalsFromPayload(payload) {
    if (!elTotals) return;

    const t = payload && payload.totals ? payload.totals : {};
    elTotals.textContent =
      `Calories: ${round0(t.calories)}\n` +
      `Protein(g): ${round1(t.protein)}\n` +
      `Carbs(g): ${round1(t.carbs)}\n` +
      `Fat(g): ${round1(t.fat)}`;
  }

  function renderListFromPayload(payload) {
    if (!elList) return;

    const entries = payload && Array.isArray(payload.entries) ? payload.entries : [];
    elList.innerHTML = "";

    if (entries.length === 0) {
      const li = document.createElement("li");
      li.textContent = "No entries for this date.";
      elList.appendChild(li);
      return;
    }

    entries.forEach((x) => {
      const name = String(x.food_name || "Unknown");
      const qty = round0(Number(x.quantity ?? x.servings ?? 0));

      const li = document.createElement("li");
      const line =
        `${name} - Qty ${qty} | ` +
        `Cal ${round0(x.calories)} ` +
        `P ${round1(x.protein_g)} ` +
        `C ${round1(x.carbs_g)} ` +
        `F ${round1(x.fat_g)}`;

      const btn = document.createElement("button");
      btn.type = "button";
      btn.textContent = "Delete";
      btn.addEventListener("click", async function () {
        try {
          await fetchJSON(`${API_BASE}/logs_delete.php`, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ user_id: USER_ID, id: Number(x.id) })
          });
          await refreshDayOnly();
        } catch (e) {
          alert(String(e.message || "Delete failed"));
        }
      });

      li.textContent = line + " ";
      li.appendChild(btn);
      elList.appendChild(li);
    });
  }

  async function refreshDayOnly() {
    let payload;
    try {
      payload = await loadDay();
    } catch (e) {
      if (elTotals) elTotals.textContent = "";
      if (elList) {
        elList.innerHTML = "";
        const li = document.createElement("li");
        li.textContent = String(e.message || "Failed to load logs");
        elList.appendChild(li);
      }
      return;
    }

    renderTotalsFromPayload(payload);
    renderListFromPayload(payload);
  }

  async function addEntry() {
    const q = elFood ? String(elFood.value || "").trim() : "";
    const quantity = Number((elQty && elQty.value) || 0);
    const log_date = activeDate();

    if (!q) {
      alert("Please enter a food name.");
      return;
    }

    if (!Number.isFinite(quantity) || quantity <= 0 || !Number.isInteger(quantity)) {
      alert("Please enter quantity as an integer > 0.");
      return;
    }

    try {
      const res = await fetchJSON(`${API_BASE}/logs_add.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ user_id: USER_ID, log_date, q, quantity })
      });

      if (res && res.status === "NO RESULT") {
        alert("No matching food found in Food Database.");
        return;
      }

      elFood.value = "";
      await refreshDayOnly();
    } catch (e) {
      alert(String(e.message || "Add failed"));
    }
  }

  async function init() {
    if (elDate) elDate.value = todayISO();
    if (elQty) {
      elQty.min = "1";
      elQty.step = "1";
      if (!elQty.value || Number(elQty.value) <= 0) elQty.value = "1";
    }

    await renderFoodOptions();
    await refreshDayOnly();

    if (elAdd) elAdd.addEventListener("click", addEntry);
    if (elDate) elDate.addEventListener("change", refreshDayOnly);
    window.refreshDietLogFoods = renderFoodOptions;
  }

  document.addEventListener("DOMContentLoaded", init);
})();
