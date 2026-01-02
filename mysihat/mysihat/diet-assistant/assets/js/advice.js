(function () {
  const API_BASE = "/diet-assistant/api";
  const USER_ID = 1;

  const elAdvice = document.getElementById("dietAdvice");
  const elBtn = document.getElementById("generateAdviceBtn");
  const elStart = document.getElementById("reportStart");
  const elEnd = document.getElementById("reportEnd");

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

  async function fetchJSON(url) {
    const res = await fetch(url, { method: "GET", cache: "no-store" });
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

  async function generateAdvice() {
    if (!elAdvice) return;

    const start = sanitizeDate(elStart ? elStart.value : "");
    const end = sanitizeDate(elEnd ? elEnd.value : "");

    const startUse = start || daysAgoISO(6);
    const endUse = end || todayISO();

    if (startUse > endUse) {
      elAdvice.textContent = "Start must be <= End.";
      return;
    }

    const oldText = elBtn ? elBtn.textContent : "";
    if (elBtn) {
      elBtn.disabled = true;
      elBtn.textContent = "Generating...";
    }

    elAdvice.textContent = "Loading...";

    try {
      const url =
        `${API_BASE}/advice_generate.php?user_id=${encodeURIComponent(USER_ID)}` +
        `&start=${encodeURIComponent(startUse)}` +
        `&end=${encodeURIComponent(endUse)}`;

      const data = await fetchJSON(url);

      if (!data || data.success !== true) {
        elAdvice.textContent = (data && (data.message || data.error)) ? (data.message || data.error) : "Generate advice failed.";
        return;
      }

      elAdvice.textContent = String(data.advice || "");
    } catch (e) {
      elAdvice.textContent = String(e && e.message ? e.message : "Network error");
    } finally {
      if (elBtn) {
        elBtn.disabled = false;
        elBtn.textContent = oldText || "Generate Diet Advice";
      }
    }
  }

  function init() {
    if (!elBtn) return;
    elBtn.addEventListener("click", generateAdvice);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
