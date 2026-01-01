/**
 * Training Module JavaScript
 * Handles workout timer, real-time updates, and finish workout
 */

(function() {
  'use strict';

  // Workout timer
  let timerInterval = null;
  let timerStartTime = null;

  function startTimer() {
    const timerDisplay = document.getElementById('timer-display');
    if (!timerDisplay) return;

    // Get start time from page (workout start_time)
    const workoutStartEl = document.querySelector('[data-workout-start]');
    if (workoutStartEl) {
      timerStartTime = new Date(workoutStartEl.dataset.workoutStart);
    } else {
      timerStartTime = new Date();
    }

    function updateTimer() {
      const now = new Date();
      const diff = Math.floor((now - timerStartTime) / 1000);
      const hours = Math.floor(diff / 3600);
      const minutes = Math.floor((diff % 3600) / 60);
      const seconds = diff % 60;
      timerDisplay.textContent = 
        String(hours).padStart(2, '0') + ':' +
        String(minutes).padStart(2, '0') + ':' +
        String(seconds).padStart(2, '0');
    }

    updateTimer();
    timerInterval = setInterval(updateTimer, 1000);
  }

  // Debounce function
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

    fetch('/mysihat/training/api/update_item.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
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
      if (!data.ok) {
        console.error('Update failed:', data.error);
      }
    })
    .catch(err => {
      console.error('Update error:', err);
    });
  }

  // Debounced update function
  const debouncedUpdate = debounce(updateWorkoutItem, 500);

  // RPE slider update
  function setupRpeSliders() {
    document.querySelectorAll('.item-rpe').forEach(slider => {
      slider.addEventListener('input', function() {
        const itemId = this.dataset.itemId;
        const valueEl = document.getElementById('rpe-value-' + itemId);
        if (valueEl) {
          valueEl.textContent = this.value;
        }
        const itemEl = this.closest('.workout-item');
        if (itemEl) {
          debouncedUpdate(itemEl);
        }
      });
    });
  }

  // Completion checkbox update
  function setupCompletionCheckboxes() {
    document.querySelectorAll('.item-completed').forEach(checkbox => {
      checkbox.addEventListener('change', function() {
        const itemEl = this.closest('.workout-item');
        if (itemEl) {
          debouncedUpdate(itemEl);
        }
        updateCompletedCount();
      });
    });
  }

  // Update completed count
  function updateCompletedCount() {
    const completedCount = document.querySelectorAll('.item-completed:checked').length;
    const countEl = document.getElementById('completed-count');
    if (countEl) {
      countEl.textContent = completedCount;
    }
  }

  // Finish workout
  function setupFinishWorkout() {
    const finishBtn = document.getElementById('finish-workout-btn');
    if (!finishBtn) return;

    finishBtn.addEventListener('click', function() {
      const workoutId = parseInt(document.getElementById('workout-form')?.dataset.workoutId || 0);
      if (!workoutId) {
        alert('Invalid workout ID');
        return;
      }

      if (!confirm('Finish this workout? This will save all your progress.')) {
        return;
      }

      finishBtn.disabled = true;
      finishBtn.textContent = 'Finishing...';

      fetch('/mysihat/training/api/finish_workout.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          workout_id: workoutId,
        }),
      })
      .then(res => res.json())
      .then(data => {
        if (data.ok) {
          // Show summary modal
          const summary = data.summary;
          const modalHtml = `
            <div class="modal fade" id="finishModal" tabindex="-1">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title">Workout Complete!</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <div class="row g-3">
                      <div class="col-6">
                        <div class="small-muted">Completion</div>
                        <div class="fs-4 fw-bold">${summary.completion_rate}%</div>
                      </div>
                      <div class="col-6">
                        <div class="small-muted">Duration</div>
                        <div class="fs-4 fw-bold">${summary.duration_min} min</div>
                      </div>
                      <div class="col-6">
                        <div class="small-muted">Avg RPE</div>
                        <div class="fs-4 fw-bold">${summary.avg_rpe || 'N/A'}</div>
                      </div>
                      <div class="col-6">
                        <div class="small-muted">Exercises</div>
                        <div class="fs-4 fw-bold">${summary.completed_items}/${summary.total_items}</div>
                      </div>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <a href="/mysihat/training/history.php" class="btn btn-primary">View History</a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                  </div>
                </div>
              </div>
            </div>
          `;
          document.body.insertAdjacentHTML('beforeend', modalHtml);
          const modal = new bootstrap.Modal(document.getElementById('finishModal'));
          modal.show();
          
          // Redirect after modal closes
          document.getElementById('finishModal').addEventListener('hidden.bs.modal', function() {
            window.location.href = '/mysihat/training/history.php';
          });
        } else {
          alert('Error: ' + (data.error || 'Failed to finish workout'));
          finishBtn.disabled = false;
          finishBtn.innerHTML = '<i class="bi bi-check-circle"></i> Finish Workout';
        }
      })
      .catch(err => {
        console.error('Finish error:', err);
        alert('Network error. Please try again.');
        finishBtn.disabled = false;
        finishBtn.innerHTML = '<i class="bi bi-check-circle"></i> Finish Workout';
      });
    });
  }

  // Initialize on page load
  document.addEventListener('DOMContentLoaded', function() {
    startTimer();
    setupRpeSliders();
    setupCompletionCheckboxes();
    setupFinishWorkout();
    updateCompletedCount();
  });

  // Cleanup on page unload
  window.addEventListener('beforeunload', function() {
    if (timerInterval) {
      clearInterval(timerInterval);
    }
  });


  document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("workout-form");
  if (!form) return;

  const workoutId = Number(form.dataset.workoutId || 0);
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

  // 1) If finished: show final duration and do NOT run timer
  if (endTimeStr) {
    const sec = durationMinStr ? Math.max(0, Math.round(parseFloat(durationMinStr) * 60)) : 0;
    timerEl.textContent = formatHHMMSS(sec);
    return;
  }

  // 2) Active workout: timer with pause/resume (stored in localStorage)
  const startMs = new Date(startTimeStr.replace(" ", "T")).getTime();

  let state = { elapsedSec: 0, paused: false };
  try {
    const saved = JSON.parse(localStorage.getItem(key));
    if (saved && typeof saved.elapsedSec === "number") state = { ...state, ...saved };
  } catch (_) {}

  const saveState = () => localStorage.setItem(key, JSON.stringify(state));

  // Initialize elapsed if first time
  if (state.elapsedSec === 0) {
    state.elapsedSec = Math.max(0, Math.floor((Date.now() - startMs) / 1000));
    saveState();
  }

  timerEl.textContent = formatHHMMSS(state.elapsedSec);

  const renderPauseText = () => {
    if (pauseBtn) pauseBtn.textContent = state.paused ? "Resume" : "Pause";
  };
  renderPauseText();

  let interval = setInterval(() => {
    if (state.paused) return;
    state.elapsedSec += 1;
    timerEl.textContent = formatHHMMSS(state.elapsedSec);
    // you can reduce save frequency later; keep simple now
    saveState();
  }, 1000);

  if (pauseBtn) {
    pauseBtn.addEventListener("click", () => {
      state.paused = !state.paused;
      saveState();
      renderPauseText();
    });
  }

  // 3) Finish: send duration_min based on elapsedSec (pause excluded)
  if (finishBtn) {
    finishBtn.addEventListener("click", async () => {
      const durationMin = Math.max(1, Math.round(state.elapsedSec / 60));

      try {
        // 推荐：相对路径（最稳）
        const res = await fetch("./api/finish_workout.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ workout_id: workoutId, duration_min: durationMin })
        });

        const data = await res.json();
        if (!data || !data.ok) {
          alert(data?.error || "Finish failed");
          return;
        }

        clearInterval(interval);
        localStorage.removeItem(key);
        window.location.reload();
      } catch (e) {
        console.error(e);
        alert("Finish failed");
      }
    });
  }
});

})();

