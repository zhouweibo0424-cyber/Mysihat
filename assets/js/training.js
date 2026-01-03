/**
 * Training Module JavaScript - Fixed Version
 * Handles workout timer (client-side duration), auto-save, and finishing.
 */

(function() {
  'use strict';

  // ==========================================
  // 1. Helper Functions (Debounce & API)
  // ==========================================

  // Debounce function to prevent API spamming
  function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  }

  // Update workout item via API
  function updateWorkoutItem(itemEl) {
    const workoutId = parseInt(document.getElementById('workout-form')?.dataset.workoutId || 0);
    const itemId = parseInt(itemEl.dataset.itemId || 0);
    const exerciseId = parseInt(itemEl.dataset.exerciseId || 0);

    if (!workoutId || !exerciseId) return;

    const isCompleted = itemEl.querySelector('.item-completed')?.checked ? 1 : 0;
    const rpe = parseInt(itemEl.querySelector('.item-rpe')?.value || 0) || null;
    const sets = parseInt(itemEl.querySelector('.item-sets')?.value || 0) || null;
    const reps = parseInt(itemEl.querySelector('.item-reps')?.value || 0) || null;
    const weight = parseFloat(itemEl.querySelector('.item-weight')?.value || 0) || null;

    fetch('api/update_item.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        workout_id: workoutId,
        exercise_id: exerciseId,
        is_completed: isCompleted,
        rpe: rpe,
        sets: sets,
        reps: reps,
        weight: weight,
      }),
    })
    .then(res => res.json())
    .then(data => {
      if (!data.ok) console.error('Update failed:', data.error);
    })
    .catch(err => console.error('Update error:', err));
  }

  const debouncedUpdate = debounce(updateWorkoutItem, 500);

  // ==========================================
  // 2. UI Setup Functions (RPE & Checkboxes)
  // ==========================================

  function setupRpeSliders() {
    document.querySelectorAll('.item-rpe').forEach(slider => {
      slider.addEventListener('input', function() {
        const itemId = this.dataset.itemId;
        // Update the number display next to slider
        const valueEl = document.getElementById('rpe-value-' + itemId) || document.getElementById('rpe-val-' + itemId);
        if (valueEl) valueEl.textContent = this.value;
        
        const itemEl = this.closest('.workout-item');
        if (itemEl) debouncedUpdate(itemEl);
      });
    });
  }

  function setupCompletionCheckboxes() {
    document.querySelectorAll('.item-completed').forEach(checkbox => {
      checkbox.addEventListener('change', function() {
        const itemEl = this.closest('.workout-item');
        if (itemEl) debouncedUpdate(itemEl);
        updateCompletedCount();
      });
    });
  }

  function updateCompletedCount() {
    const completedCount = document.querySelectorAll('.item-completed:checked').length;
    const countEl = document.getElementById('completed-count');
    if (countEl) countEl.textContent = completedCount;
  }

  // ==========================================
  // 3. Main Initialization & Timer Logic
  // ==========================================

  document.addEventListener("DOMContentLoaded", () => {
    // A. Initialize basic UI handlers
    setupRpeSliders();
    setupCompletionCheckboxes();
    updateCompletedCount();

    // B. TIMER & FINISH LOGIC (Your Code Integrated)
    const form = document.getElementById("workout-form");
    if (!form) return;

    const workoutId = Number(form.dataset.workoutId || 0);
    // Note: Ensure PHP outputs these data attributes correctly!
    const startTimeStr = form.dataset.workoutStart || ""; 
    const endTimeStr = form.dataset.workoutEnd || "";     
    const durationMinStr = form.dataset.durationMin || "";

    const timerEl = document.getElementById("timer-display");
    const pauseBtn = document.getElementById("pause-resume-btn");
    const finishBtn = document.getElementById("finish-workout-btn");

    if (!workoutId || !timerEl) return;

    const key = `workout_timer_${workoutId}`;

    const formatHHMMSS = (sec) => {
      const h = Math.floor(sec / 3600);
      const m = Math.floor((sec % 3600) / 60);
      const s = sec % 60;
      return String(h).padStart(2, "0") + ":" + String(m).padStart(2, "0") + ":" + String(s).padStart(2, "0");
    };

    // --- Scenario 1: Workout Finished ---
    // If we have an end time from PHP, show static duration and EXIT.
    // This fixes the "timer keeps running" bug.
    if (endTimeStr) {
      const sec = durationMinStr ? Math.max(0, Math.round(parseFloat(durationMinStr) * 60)) : 0;
      timerEl.textContent = formatHHMMSS(sec);
      return; // Stop here, do not start setInterval
    }

    // --- Scenario 2: Active Workout ---
    // Timer with pause/resume (stored in localStorage)
    const startMs = new Date(startTimeStr.replace(/-/g, '/')).getTime(); // Replace - with / for better Safari support

    let state = { elapsedSec: 0, paused: false };
    try {
      const saved = JSON.parse(localStorage.getItem(key));
      if (saved && typeof saved.elapsedSec === "number") state = { ...state, ...saved };
    } catch (_) {}

    const saveState = () => localStorage.setItem(key, JSON.stringify(state));

    // Initialize elapsed if first time or lost
    if (state.elapsedSec === 0 && !isNaN(startMs)) {
      // Calculate initial elapsed based on server start time vs now
      state.elapsedSec = Math.max(0, Math.floor((Date.now() - startMs) / 1000));
      saveState();
    }

    timerEl.textContent = formatHHMMSS(state.elapsedSec);

    const renderPauseText = () => {
      if (pauseBtn) pauseBtn.textContent = state.paused ? "Resume" : "Pause";
    };
    renderPauseText();

    // The Interval
    let interval = setInterval(() => {
      if (state.paused) return;
      state.elapsedSec += 1;
      timerEl.textContent = formatHHMMSS(state.elapsedSec);
      saveState(); // Save every second (robust but heavy IO? It's fine for localstorage)
    }, 1000);

    if (pauseBtn) {
      pauseBtn.addEventListener("click", () => {
        state.paused = !state.paused;
        saveState();
        renderPauseText();
      });
    }

    // --- Scenario 3: Finish Action ---
    // Calculates duration on Client Side and sends to API
    if (finishBtn) {
      finishBtn.addEventListener("click", async () => {
        if (!confirm('Finish this workout?')) return;
        
        // Disable button to prevent double submit
        finishBtn.disabled = true;
        finishBtn.innerHTML = 'Finishing...';

        // Calculate minutes (minimum 1)
        const durationMin = Math.max(1, Math.round(state.elapsedSec / 60));

        try {
          const res = await fetch("api/finish_workout.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ workout_id: workoutId, duration_min: durationMin })
          });

          const data = await res.json();
          if (!data || !data.ok) {
            alert(data?.error || "Finish failed");
            finishBtn.disabled = false;
            finishBtn.textContent = 'Finish Workout';
            return;
          }

          // Cleanup
          clearInterval(interval);
          localStorage.removeItem(key);
          
          // Reload page to show "Completed" state (Green alert)
          window.location.reload();
        } catch (e) {
          console.error(e);
          alert("Network error during finish");
          finishBtn.disabled = false;
        }
      });
    }
  });

})();