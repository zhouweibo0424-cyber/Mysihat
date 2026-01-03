document.addEventListener("DOMContentLoaded", () => {
  const API_ADD = "/diet-assistant/api/food_add.php";
  const API_GET = "/diet-assistant/api/food_get.php";

  const elName = document.getElementById("name");
  const elCalories = document.getElementById("calories");
  const elProtein = document.getElementById("protein_g");
  const elCarbs = document.getElementById("carbs_g");
  const elFat = document.getElementById("fat_g");
  const addBtn = document.getElementById("addFoodBtn");

  const elQuery = document.getElementById("foodSearchQuery");
  const searchBtn = document.getElementById("searchFoodBtn");

  const elMsg = document.getElementById("foodMsg");
  const elResults = document.getElementById("foodSearchResults");

  const setMsg = (text, isError) => {
    if (!elMsg) return;
    elMsg.textContent = text || "";
    elMsg.style.color = isError ? "#b00020" : "";
  };

  const hideResults = () => {
    if (!elResults) return;
    elResults.style.display = "none";
    elResults.innerHTML = "";
  };

  const toNonNegativeNumber = (v) => {
    const s = String(v ?? "").trim();
    if (s === "") return null;
    const n = Number(s);
    if (!Number.isFinite(n)) return null;
    if (n < 0) return null;
    return n;
  };

  const normalizeFoods = (data) => {
    if (Array.isArray(data)) return data;
    if (data && Array.isArray(data.foods)) return data.foods;
    if (data && Array.isArray(data.data)) return data.data;
    return [];
  };

  const renderFoods = (foods, q) => {
    if (!elResults) return;

    const queryText = String(q || "").trim();

    if (!foods || foods.length === 0) {
      elResults.innerHTML = `<div class="muted">No results${queryText ? ` for "${queryText}"` : ""}.</div>`;
      elResults.style.display = "block";
      return;
    }

    const rows = foods
      .map((f) => {
        const id = f.id ?? "";
        const name = f.name ?? f.food_name ?? "";
        const calories = f.calories ?? f.food_calories ?? "";
        const protein = f.protein_g ?? f.food_protein ?? "";
        const carbs = f.carbs_g ?? f.food_carbs ?? "";
        const fat = f.fat_g ?? f.food_fat ?? "";
        return `<tr>
          <td>${String(id)}</td>
          <td>${String(name)}</td>
          <td>${String(calories)}</td>
          <td>${String(protein)}</td>
          <td>${String(carbs)}</td>
          <td>${String(fat)}</td>
        </tr>`;
      })
      .join("");

    elResults.innerHTML = `
      <div class="muted" style="margin-bottom:10px;">Found ${foods.length} result(s)${queryText ? ` for "${queryText}"` : ""}.</div>
      <table>
        <thead>
          <tr>
            <th style="text-align:left;">ID</th>
            <th style="text-align:left;">Name</th>
            <th style="text-align:left;">Calories</th>
            <th style="text-align:left;">Protein (g)</th>
            <th style="text-align:left;">Carbs (g)</th>
            <th style="text-align:left;">Fat (g)</th>
          </tr>
        </thead>
        <tbody>${rows}</tbody>
      </table>
    `;
    elResults.style.display = "block";
  };

  const fetchJson = async (url, options) => {
    const r = await fetch(url, options);
    const data = await r.json().catch(() => ({}));
    if (!r.ok) throw new Error(data.message || `HTTP ${r.status}`);
    return data;
  };

  hideResults();
  setMsg("");

  if (addBtn) {
    addBtn.addEventListener("click", async () => {
      setMsg("");
      hideResults();

      const name = String(elName?.value ?? "").trim();
      const calories = toNonNegativeNumber(elCalories?.value);
      const protein_g = toNonNegativeNumber(elProtein?.value);
      const carbs_g = toNonNegativeNumber(elCarbs?.value);
      const fat_g = toNonNegativeNumber(elFat?.value);

      if (!name) {
        setMsg("Food name is required.", true);
        return;
      }

      if (calories === null || protein_g === null || carbs_g === null || fat_g === null) {
        setMsg("Calories/Protein/Carbs/Fat must be non-negative numbers.", true);
        return;
      }

      try {
        const data = await fetchJson(API_ADD, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          cache: "no-store",
          body: JSON.stringify({ name, calories, protein_g, carbs_g, fat_g })
        });

        const ok = data.success === true || data.status === "success" || data.ok === true;
        if (!ok) {
          setMsg(data.message || "Add food failed.", true);
          return;
        }

        setMsg(data.message || "Food added.");
        if (elName) elName.value = "";
        if (elCalories) elCalories.value = "";
        if (elProtein) elProtein.value = "";
        if (elCarbs) elCarbs.value = "";
        if (elFat) elFat.value = "";
      } catch (e) {
        setMsg(String(e?.message || e || "Network error"), true);
      }
    });
  }

  const doSearch = async () => {
    const q = String(elQuery?.value ?? "").trim();

    if (!q) {
      hideResults();
      setMsg("Enter a keyword to search.", true);
      return;
    }

    setMsg("Searching...");

    try {
      const data = await fetchJson(`${API_GET}?q=${encodeURIComponent(q)}`, {
        method: "GET",
        cache: "no-store"
      });

      const foods = normalizeFoods(data);
      renderFoods(foods, q);
      setMsg("");
    } catch (e) {
      hideResults();
      setMsg(String(e?.message || e || "Search failed."), true);
    }
  };

  if (searchBtn) searchBtn.addEventListener("click", doSearch);

  if (elQuery) {
    elQuery.addEventListener("keydown", (ev) => {
      if (ev.key === "Enter") doSearch();
    });

    elQuery.addEventListener("input", () => {
      const q = String(elQuery.value || "").trim();
      if (!q) {
        hideResults();
        setMsg("");
      }
    });
  }
});
