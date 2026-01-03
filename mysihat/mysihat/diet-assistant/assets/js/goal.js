(function () {
  const USER_ID = 1;

  function setText(id, text) {
    const el = document.getElementById(id);
    if (el) el.textContent = text;
  }

  function getVal(id) {
    const el = document.getElementById(id);
    return el ? el.value : "";
  }

  function setVal(id, v) {
    const el = document.getElementById(id);
    if (el) el.value = v ?? "";
  }

  function toNum(v) {
    const n = Number(v);
    return Number.isFinite(n) ? n : null;
  }

  function render(goal) {
    setText("output", goal ? JSON.stringify(goal, null, 2) : "");
  }

  function validateNonNegative(payload) {
    const fields = ["daily_calorie_target", "protein_target_g", "carbs_target_g", "fat_target_g"];
    for (const k of fields) {
      const v = payload[k];
      if (v === null || v === undefined || v === "") return `Missing ${k}`;
      const n = Number(v);
      if (!Number.isFinite(n)) return `Invalid ${k}`;
      if (n < 0) return `Negative ${k} is not allowed`;
    }
    return "";
  }

  async function apiGetGoal() {
    const res = await fetch(`api/goal_get.php?user_id=${USER_ID}&_t=${Date.now()}`, { method: "GET" });
    const data = await res.json();
    if (!data || data.success !== true) throw new Error(data?.message || "Goal get failed");
    return data.data || null;
  }

  async function apiSetGoal(payload) {
    const res = await fetch("api/goal_set.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (!data || data.success !== true) throw new Error(data?.message || "Goal set failed");
    return data;
  }

  async function loadGoal() {
    setText("status", "Loading...");
    try {
      const g = await apiGetGoal();
      if (!g) {
        setText("status", "No goal found");
        render(null);
        return;
      }

      setVal("goal_type", g.goal_type);
      setVal("daily_calorie_target", g.daily_calorie_target);
      setVal("protein_target_g", g.protein_target_g);
      setVal("carbs_target_g", g.carbs_target_g);
      setVal("fat_target_g", g.fat_target_g);

      render(g);
      setText("status", "Goal loaded");
    } catch (e) {
      setText("status", String(e.message || e));
    }
  }

  async function saveGoal() {
    const payload = {
      user_id: USER_ID,
      goal_type: String(getVal("goal_type")).trim(),
      daily_calorie_target: toNum(getVal("daily_calorie_target")),
      protein_target_g: toNum(getVal("protein_target_g")),
      carbs_target_g: toNum(getVal("carbs_target_g")),
      fat_target_g: toNum(getVal("fat_target_g"))
    };

    const err = validateNonNegative(payload);
    if (err) {
      setText("status", err);
      alert(err);
      return;
    }

    setText("status", "Saving...");

    try {
      await apiSetGoal(payload);
      const latest = await apiGetGoal();

      if (latest) {
        setVal("goal_type", latest.goal_type);
        setVal("daily_calorie_target", latest.daily_calorie_target);
        setVal("protein_target_g", latest.protein_target_g);
        setVal("carbs_target_g", latest.carbs_target_g);
        setVal("fat_target_g", latest.fat_target_g);
      }

      render(latest);
      setText("status", "Goal saved");
    } catch (e) {
      setText("status", String(e.message || e));
    }
  }

  document.addEventListener("DOMContentLoaded", function () {
    loadGoal();
    const btn = document.getElementById("saveBtn");
    if (btn) btn.addEventListener("click", saveGoal);
  });
})();
