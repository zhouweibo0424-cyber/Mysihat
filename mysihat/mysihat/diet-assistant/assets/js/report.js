(function () {
  const API_BASE = "/diet-assistant/api";
  const USER_ID = 1;

  const elStart = document.getElementById("reportStart");
  const elEnd = document.getElementById("reportEnd");
  const elBtn = document.getElementById("generateReportBtn");
  const elHint = document.getElementById("reportHint");
  const elSummary = document.getElementById("reportSummary");
  const elDaily = document.getElementById("reportDailyTable");
  const elTop = document.getElementById("reportTopFoods");

  function setHint(t) {
    if (!elHint) return;
    elHint.textContent = t || "";
  }

  function todayISO() {
    const d = new Date();
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");
    return `${y}-${m}-${day}`;
  }

  function daysAgoISO(n) {
    const d = new Date();
    d.setDate(d.getDate() - n);
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");
    return `${y}-${m}-${day}`;
  }

  function sanitizeDate(s) {
    const v = String(s || "").trim();
    if (!v) return "";
    const x = v.replace(/\//g, "-");
    if (/^\d{4}-\d{2}-\d{2}$/.test(x)) return x;
    return "";
  }

  function toNum(n) {
    const x = Number(n || 0);
    return Number.isFinite(x) ? x : 0;
  }

  function round0(n) {
    return Math.round(toNum(n));
  }

  function addDays(dateISO, delta) {
    const [y, m, d] = dateISO.split("-").map((x) => parseInt(x, 10));
    const dt = new Date(y, m - 1, d);
    dt.setDate(dt.getDate() + delta);
    const yy = dt.getFullYear();
    const mm = String(dt.getMonth() + 1).padStart(2, "0");
    const dd = String(dt.getDate()).padStart(2, "0");
    return `${yy}-${mm}-${dd}`;
  }

  function dateLE(a, b) {
    return String(a) <= String(b);
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

  async function loadDay(dateISO) {
    const url = `${API_BASE}/logs_get.php?user_id=${encodeURIComponent(USER_ID)}&log_date=${encodeURIComponent(dateISO)}`;
    return fetchJSON(url, { method: "GET", cache: "no-store" });
  }

  function renderSummary(rangeDays, totalEntries, totals, avgs) {
    if (!elSummary) return;

    let text = "";
    text += `Range days: ${rangeDays}\n`;
    text += `Total entries: ${totalEntries}\n\n`;

    text += `Total Calories: ${round0(totals.calories)}\n`;
    text += `Total Protein(g): ${round0(totals.protein)}\n`;
    text += `Total Carbs(g): ${round0(totals.carbs)}\n`;
    text += `Total Fat(g): ${round0(totals.fat)}\n\n`;

    text += `Avg Calories/day: ${round0(avgs.calories)}\n`;
    text += `Avg Protein/day(g): ${round0(avgs.protein)}\n`;
    text += `Avg Carbs/day(g): ${round0(avgs.carbs)}\n`;
    text += `Avg Fat/day(g): ${round0(avgs.fat)}\n`;

    elSummary.textContent = text;
  }

  function renderDailyTable(rows) {
    if (!elDaily) return;

    if (!rows || rows.length === 0) {
      elDaily.innerHTML = `<div class="muted">No data in selected range.</div>`;
      return;
    }

    const body = rows
      .map((r) => {
        return `<tr>
          <td>${r.date}</td>
          <td>${round0(r.calories)}</td>
          <td>${round0(r.protein)}</td>
          <td>${round0(r.carbs)}</td>
          <td>${round0(r.fat)}</td>
          <td>${r.entries}</td>
        </tr>`;
      })
      .join("");

    elDaily.innerHTML = `
      <table>
        <thead>
          <tr>
            <th style="text-align:left;">Date</th>
            <th style="text-align:left;">Calories</th>
            <th style="text-align:left;">Protein(g)</th>
            <th style="text-align:left;">Carbs(g)</th>
            <th style="text-align:left;">Fat(g)</th>
            <th style="text-align:left;">Entries</th>
          </tr>
        </thead>
        <tbody>${body}</tbody>
      </table>
    `;
  }

  function renderTopFoods(items) {
    if (!elTop) return;

    if (!items || items.length === 0) {
      elTop.innerHTML = `<div class="muted">No data in selected range.</div>`;
      return;
    }

    const body = items
      .map((x) => {
        return `<tr>
          <td>${x.food_name}</td>
          <td>${x.entries}</td>
          <td>${x.quantity}</td>
          <td>${round0(x.calories)}</td>
        </tr>`;
      })
      .join("");

    elTop.innerHTML = `
      <table>
        <thead>
          <tr>
            <th style="text-align:left;">Food</th>
            <th style="text-align:left;">Entries</th>
            <th style="text-align:left;">Quantity</th>
            <th style="text-align:left;">Calories</th>
          </tr>
        </thead>
        <tbody>${body}</tbody>
      </table>
    `;
  }

  async function generate() {
    const start = sanitizeDate(elStart ? elStart.value : "");
    const end = sanitizeDate(elEnd ? elEnd.value : "");

    if (!start || !end) {
      setHint("Please select valid start/end dates.");
      return;
    }
    if (!dateLE(start, end)) {
      setHint("Start must be <= End.");
      return;
    }

    setHint("Generating...");
    if (elSummary) elSummary.textContent = "";
    if (elDaily) elDaily.innerHTML = "";
    if (elTop) elTop.innerHTML = "";

    const dailyRows = [];
    const totals = { calories: 0, protein: 0, carbs: 0, fat: 0 };
    let totalEntries = 0;
    const topMap = new Map();

    let cur = start;
    let safety = 0;
    while (dateLE(cur, end) && safety < 400) {
      safety += 1;

      let dayPayload;
      try {
        dayPayload = await loadDay(cur);
      } catch {
        dayPayload = { entries: [], totals: { calories: 0, protein: 0, carbs: 0, fat: 0 } };
      }

      const entries = Array.isArray(dayPayload.entries) ? dayPayload.entries : [];
      const t = dayPayload.totals || {};

      const dayCalories = toNum(t.calories);
      const dayProtein = toNum(t.protein);
      const dayCarbs = toNum(t.carbs);
      const dayFat = toNum(t.fat);

      totals.calories += dayCalories;
      totals.protein += dayProtein;
      totals.carbs += dayCarbs;
      totals.fat += dayFat;

      totalEntries += entries.length;

      entries.forEach((e) => {
        const name = String(e.food_name || "").trim();
        if (!name) return;
        const cal = toNum(e.calories);
        const qty = Math.round(toNum(e.quantity ?? e.servings ?? 0));
        const prev = topMap.get(name) || { food_name: name, entries: 0, quantity: 0, calories: 0 };
        prev.entries += 1;
        prev.quantity += qty > 0 ? qty : 0;
        prev.calories += cal;
        topMap.set(name, prev);
      });

      dailyRows.push({
        date: cur,
        calories: dayCalories,
        protein: dayProtein,
        carbs: dayCarbs,
        fat: dayFat,
        entries: entries.length
      });

      cur = addDays(cur, 1);
    }

    const rangeDays = dailyRows.length;
    const avgs = {
      calories: rangeDays > 0 ? totals.calories / rangeDays : 0,
      protein: rangeDays > 0 ? totals.protein / rangeDays : 0,
      carbs: rangeDays > 0 ? totals.carbs / rangeDays : 0,
      fat: rangeDays > 0 ? totals.fat / rangeDays : 0
    };

    const topFoods = Array.from(topMap.values())
      .sort((a, b) => (b.calories - a.calories) || (b.entries - a.entries))
      .slice(0, 10);

    renderSummary(rangeDays, totalEntries, totals, avgs);
    renderDailyTable(dailyRows);
    renderTopFoods(topFoods);

    setHint("");
  }

  function init() {
    if (elStart && !elStart.value) elStart.value = daysAgoISO(6);
    if (elEnd && !elEnd.value) elEnd.value = todayISO();

    setHint("Report JS loaded.");
    if (elBtn) elBtn.addEventListener("click", generate);
    if (!elBtn) setHint("Generate button not found.");
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
