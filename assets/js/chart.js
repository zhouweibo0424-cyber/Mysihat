/* mysihat/assets/js/chart.js
   SimpleCanvasChart: lightweight charts (pie / line / bar) without external libraries.
*/
(function () {
  const DEFAULT_COLORS = ["#2563eb", "#16a34a", "#f97316", "#a855f7", "#ef4444", "#0ea5e9", "#84cc16"];

  function ensureCanvasSize(canvas) {
    const dpr = window.devicePixelRatio || 1;
    const rect = canvas.getBoundingClientRect();
    const w = Math.max(1, Math.floor(rect.width * dpr));
    const h = Math.max(1, Math.floor(rect.height * dpr));
    if (canvas.width !== w || canvas.height !== h) {
      canvas.width = w;
      canvas.height = h;
    }
    return { w, h, dpr };
  }

  function clear(canvas) {
    const ctx = canvas.getContext("2d");
    const { w, h } = ensureCanvasSize(canvas);
    ctx.clearRect(0, 0, w, h);
  }

  function drawLegend(ctx, x, y, items, colors, fontPx, maxWidth) {
    ctx.save();
    ctx.font = `${fontPx}px system-ui, -apple-system, Segoe UI, Roboto, Arial`;
    ctx.textBaseline = "middle";

    const box = Math.max(10, Math.floor(fontPx * 0.9));
    const gap = 18;
    const rowGap = Math.max(14, Math.floor(fontPx * 1.4));
    const limit = (typeof maxWidth === "number" && maxWidth > 0) ? maxWidth : Infinity;

    let cx = x;
    let cy = y;

    for (let i = 0; i < items.length; i++) {
      const label = String(items[i] ?? "");
      const color = colors[i % colors.length];

      const labelW = ctx.measureText(label).width;
      const itemW = box + 8 + labelW + gap;

      if (cx !== x && (cx + itemW) > limit) {
        cx = x;
        cy += rowGap;
      }

      ctx.fillStyle = color;
      ctx.fillRect(cx, cy - box / 2, box, box);

      ctx.fillStyle = "#111827";
      ctx.fillText(label, cx + box + 8, cy);

      cx = cx + itemW;
    }
    ctx.restore();
  }

  function pie(canvas, opts) {
    const ctx = canvas.getContext("2d");
    const labels = (opts && opts.labels) ? opts.labels : [];
    const data = (opts && opts.data) ? opts.data : [];
    const colors = (opts && opts.colors) ? opts.colors : DEFAULT_COLORS;

    let destroyed = false;

    function render() {
      if (destroyed) return;
      const { w, h } = ensureCanvasSize(canvas);
      ctx.clearRect(0, 0, w, h);

      const total = data.reduce((a, b) => a + (Number.isFinite(+b) ? +b : 0), 0);
      if (total <= 0) {
        ctx.save();
        ctx.fillStyle = "#6b7280";
        ctx.font = `${Math.floor(Math.min(w, h) / 14)}px system-ui, -apple-system, Segoe UI, Roboto, Arial`;
        ctx.fillText("No data", Math.floor(w * 0.08), Math.floor(h * 0.5));
        ctx.restore();
        return;
      }

      const pad = Math.floor(Math.min(w, h) * 0.08);
      const legendH = Math.floor(h * 0.22);
      const cx = Math.floor(w / 2);
      const cy = Math.floor((h - legendH) / 2);
      const r = Math.floor(Math.min(w, h - legendH) / 2) - pad;

      let start = -Math.PI / 2;
      for (let i = 0; i < data.length; i++) {
        const v = Number.isFinite(+data[i]) ? +data[i] : 0;
        const ang = (v / total) * Math.PI * 2;
        const end = start + ang;

        ctx.beginPath();
        ctx.moveTo(cx, cy);
        ctx.fillStyle = colors[i % colors.length];
        ctx.arc(cx, cy, r, start, end);
        ctx.closePath();
        ctx.fill();

        // percent label
        if (!opts || opts.showPercent !== false) {
          const pct = (v / total) * 100;
          if (pct >= 4) {
            const mid = start + ang / 2;
            const tx = cx + Math.cos(mid) * Math.floor(r * 0.78);
            const ty = cy + Math.sin(mid) * Math.floor(r * 0.78);
            const pctFont = Math.max(10, Math.floor(Math.min(w, h) / 22));
            ctx.save();
            ctx.font = `${pctFont}px system-ui, -apple-system, Segoe UI, Roboto, Arial`;
            ctx.textAlign = "center";
            ctx.textBaseline = "middle";
            ctx.lineWidth = Math.max(2, Math.floor(pctFont / 6));
            ctx.strokeStyle = "rgba(255,255,255,0.95)";
            ctx.fillStyle = "#111827";
            const text = Math.round(pct) + "%";
            ctx.strokeText(text, tx, ty);
            ctx.fillText(text, tx, ty);
            ctx.restore();
          }
        }

        start = end;
      }

      // donut hole for nicer look
      ctx.beginPath();
      ctx.fillStyle = "#f8fafc";
      ctx.arc(cx, cy, Math.floor(r * 0.55), 0, Math.PI * 2);
      ctx.fill();

      // legend (moved up + wrap)
      const fontPx = Math.max(10, Math.floor(Math.min(w, h) / 18));
      const legendY = h - Math.floor(legendH * 0.80);
      drawLegend(ctx, Math.floor(w * 0.08), legendY, labels, colors, fontPx, Math.floor(w * 0.92));
    }

    function onResize() {
      render();
    }

    window.addEventListener("resize", onResize);
    render();

    return {
      destroy() {
        destroyed = true;
        window.removeEventListener("resize", onResize);
        clear(canvas);
      }
    };
  }

  function line(canvas, opts) {
    const ctx = canvas.getContext("2d");
    const labels = (opts && opts.labels) ? opts.labels : [];
    const series = (opts && opts.series) ? opts.series : [];
    const colors = (opts && opts.colors) ? opts.colors : DEFAULT_COLORS;

    let destroyed = false;

    function render() {
      if (destroyed) return;
      const { w, h } = ensureCanvasSize(canvas);
      ctx.clearRect(0, 0, w, h);

      if (!labels.length || !series.length) {
        ctx.save();
        ctx.fillStyle = "#6b7280";
        ctx.font = `${Math.floor(Math.min(w, h) / 14)}px system-ui, -apple-system, Segoe UI, Roboto, Arial`;
        ctx.fillText("No data", Math.floor(w * 0.08), Math.floor(h * 0.5));
        ctx.restore();
        return;
      }

      const padL = Math.floor(w * 0.10);
      const padR = Math.floor(w * 0.06);
      const padT = Math.floor(h * 0.10);
      const padB = Math.floor(h * 0.18);

      const plotW = w - padL - padR;
      const plotH = h - padT - padB;

      let maxY = 0;
      for (const s of series) {
        for (const v of (s.data || [])) {
          const n = Number.isFinite(+v) ? +v : 0;
          if (n > maxY) maxY = n;
        }
      }
      if (maxY <= 0) maxY = 1;

      ctx.save();
      ctx.strokeStyle = "rgba(17,24,39,0.10)";
      ctx.lineWidth = 1;

      const gridLines = 4;
      for (let i = 0; i <= gridLines; i++) {
        const y = padT + Math.floor((plotH * i) / gridLines);
        ctx.beginPath();
        ctx.moveTo(padL, y);
        ctx.lineTo(padL + plotW, y);
        ctx.stroke();
      }

      ctx.restore();

      ctx.save();
      ctx.strokeStyle = "rgba(17,24,39,0.25)";
      ctx.lineWidth = 1.5;
      ctx.beginPath();
      ctx.moveTo(padL, padT);
      ctx.lineTo(padL, padT + plotH);
      ctx.lineTo(padL + plotW, padT + plotH);
      ctx.stroke();
      ctx.restore();

      const fontPx = Math.max(10, Math.floor(Math.min(w, h) / 18));
      ctx.save();
      ctx.fillStyle = "#6b7280";
      ctx.font = `${fontPx}px system-ui, -apple-system, Segoe UI, Roboto, Arial`;
      ctx.textAlign = "center";
      ctx.textBaseline = "top";

      const step = Math.max(1, Math.ceil(labels.length / 6));
      for (let i = 0; i < labels.length; i += step) {
        const x = padL + (plotW * i) / Math.max(1, labels.length - 1);
        ctx.fillText(String(labels[i]), x, padT + plotH + 6);
      }
      ctx.restore();

      ctx.save();
      ctx.fillStyle = "#6b7280";
      ctx.font = `${fontPx}px system-ui, -apple-system, Segoe UI, Roboto, Arial`;
      ctx.textAlign = "right";
      ctx.textBaseline = "middle";
      for (let i = 0; i <= gridLines; i++) {
        const val = maxY * (1 - i / gridLines);
        const y = padT + (plotH * i) / gridLines;
        ctx.fillText(String(Math.round(val)), padL - 8, y);
      }
      ctx.restore();

      for (let si = 0; si < series.length; si++) {
        const s = series[si] || {};
        const arr = s.data || [];
        const color = colors[si % colors.length];

        ctx.save();
        ctx.strokeStyle = color;
        ctx.lineWidth = 2;
        ctx.beginPath();

        for (let i = 0; i < arr.length; i++) {
          const v = Number.isFinite(+arr[i]) ? +arr[i] : 0;
          const x = padL + (plotW * i) / Math.max(1, arr.length - 1);
          const y = padT + plotH - (plotH * v) / maxY;
          if (i === 0) ctx.moveTo(x, y);
          else ctx.lineTo(x, y);
        }
        ctx.stroke();
        ctx.restore();
      }

      const legendItems = series.map(s => s.name || "Series");
      ctx.save();
      drawLegend(ctx, Math.floor(w * 0.08), Math.floor(h * 0.08), legendItems, colors, fontPx);
      ctx.restore();
    }

    function onResize() {
      render();
    }

    window.addEventListener("resize", onResize);
    render();

    return {
      destroy() {
        destroyed = true;
        window.removeEventListener("resize", onResize);
        clear(canvas);
      }
    };
  }

  function bar(canvas, opts) {
    const ctx = canvas.getContext("2d");
    const labels = (opts && opts.labels) ? opts.labels : [];
    const data = (opts && opts.data) ? opts.data : [];
    const color = (opts && opts.color) ? opts.color : DEFAULT_COLORS[0];

    let destroyed = false;

    function render() {
      if (destroyed) return;
      const { w, h } = ensureCanvasSize(canvas);
      ctx.clearRect(0, 0, w, h);

      if (!labels.length || !data.length) {
        ctx.save();
        ctx.fillStyle = "#6b7280";
        ctx.font = `${Math.floor(Math.min(w, h) / 14)}px system-ui, -apple-system, Segoe UI, Roboto, Arial`;
        ctx.fillText("No data", Math.floor(w * 0.08), Math.floor(h * 0.5));
        ctx.restore();
        return;
      }

      const padL = Math.floor(w * 0.10);
      const padR = Math.floor(w * 0.06);
      const padT = Math.floor(h * 0.10);
      const padB = Math.floor(h * 0.22);

      const plotW = w - padL - padR;
      const plotH = h - padT - padB;

      let maxY = 0;
      for (const v of data) {
        const n = Number.isFinite(+v) ? +v : 0;
        if (n > maxY) maxY = n;
      }
      if (maxY <= 0) maxY = 1;

      ctx.save();
      ctx.strokeStyle = "rgba(17,24,39,0.25)";
      ctx.lineWidth = 1.5;
      ctx.beginPath();
      ctx.moveTo(padL, padT);
      ctx.lineTo(padL, padT + plotH);
      ctx.lineTo(padL + plotW, padT + plotH);
      ctx.stroke();
      ctx.restore();

      const n = data.length;
      const gap = Math.max(6, Math.floor(plotW * 0.03));
      const barW = Math.max(10, Math.floor((plotW - gap * (n + 1)) / n));

      for (let i = 0; i < n; i++) {
        const v = Number.isFinite(+data[i]) ? +data[i] : 0;
        const bh = Math.floor((plotH * v) / maxY);
        const x = padL + gap + i * (barW + gap);
        const y = padT + plotH - bh;

        ctx.save();
        ctx.fillStyle = color;
        ctx.fillRect(x, y, barW, bh);
        ctx.restore();
      }

      const fontPx = Math.max(10, Math.floor(Math.min(w, h) / 18));
      ctx.save();
      ctx.fillStyle = "#6b7280";
      ctx.font = `${fontPx}px system-ui, -apple-system, Segoe UI, Roboto, Arial`;
      ctx.textAlign = "center";
      ctx.textBaseline = "top";

      for (let i = 0; i < n; i++) {
        const x = padL + gap + i * (barW + gap) + barW / 2;
        let lbl = String(labels[i] ?? "");
        if (lbl.length > 12) lbl = lbl.slice(0, 12) + "…";
        ctx.fillText(lbl, x, padT + plotH + 6);
      }
      ctx.restore();
    }

    function onResize() {
      render();
    }

    window.addEventListener("resize", onResize);
    render();

    return {
      destroy() {
        destroyed = true;
        window.removeEventListener("resize", onResize);
        clear(canvas);
      }
    };
  }

  window.SimpleCanvasChart = { pie, line, bar };
})();
