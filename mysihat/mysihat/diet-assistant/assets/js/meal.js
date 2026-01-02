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
        var url = "api/meal-recommendation.php?ingredients=" + encodeURIComponent(q);
        var res = await fetch(url);
        var data = null;
  
        try {
          data = await res.json();
        } catch (e) {
          data = null;
        }
  
        if (!res.ok) {
          setStatus("Request failed. HTTP " + res.status);
          render([]);
          return;
        }
  
        var recipes = normalize(data);
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
  
    window.__meal_js_loaded__ = true;
  
    document.addEventListener("click", function (e) {
      var btn = e.target && e.target.closest ? e.target.closest("#mealSearchBtn") : null;
      if (!btn) return;
      e.preventDefault();
      search(btn);
    }, true);
  
    setStatus("Ready.");
  })();
  