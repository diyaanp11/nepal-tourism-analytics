document.addEventListener("DOMContentLoaded", function () {
  if (!window.dashboardData) return;
  const data = window.dashboardData;

  Chart.defaults.font.family = "system-ui, -apple-system, sans-serif";
  Chart.defaults.color = "#6c757d";

  const cutoffPlugin = {
    id: "cutoffLines",
    beforeDraw: (chart) => {
      if (!data.lifecycleCutoffs) return;
      const {
        ctx,
        chartArea: { top, bottom, left, right },
        scales: { x, y },
      } = chart;

      ctx.save();
      ctx.strokeStyle = "#dc3545";
      ctx.lineWidth = 1.5;
      ctx.setLineDash([5, 5]);

      const yPixel = y.getPixelForValue(data.lifecycleCutoffs.growth);
      if (yPixel >= top && yPixel <= bottom) {
        ctx.beginPath();
        ctx.moveTo(left, yPixel);
        ctx.lineTo(right, yPixel);
        ctx.stroke();
      }

      const xPixel = x.getPixelForValue(data.lifecycleCutoffs.share);
      if (xPixel >= left && xPixel <= right) {
        ctx.beginPath();
        ctx.moveTo(xPixel, top);
        ctx.lineTo(xPixel, bottom);
        ctx.stroke();
      }
      ctx.restore();
    },
  };

  if (document.getElementById("trendChart")) {
    const trendLabels = data.trend.map((r) => `${r.year}`);
    const distinctLabels = trendLabels.map((y, i) => {
      if (i === 0 || y !== trendLabels[i - 1]) return y;
      return "";
    });

    new Chart(document.getElementById("trendChart"), {
      type: "line",
      data: {
        labels: distinctLabels,
        datasets: [
          {
            data: data.trend.map((r) => r.arrivals),
            borderColor: "#192b25",
            backgroundColor: "rgba(25, 43, 37, 0.1)",
            fill: true,
            tension: 0.3,
            pointRadius: 0,
            pointHoverRadius: 4,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: {
            grid: { display: false },
            ticks: { autoSkip: false },
          },
          y: { beginAtZero: true },
        },
      },
    });
  }

  if (document.getElementById("nationalityChart")) {
    new Chart(document.getElementById("nationalityChart"), {
      type: "doughnut",
      data: {
        labels: data.nationality.map((r) => r.name),
        datasets: [
          {
            data: data.nationality.map((r) => r.total),
            backgroundColor: [
              "#192b25",
              "#d5a45e",
              "#4a5d54",
              "#e5bd80",
              "#6c757d",
              "#8c9c93",
              "#f8d7da",
              "#0dcaf0",
            ],
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: "right", labels: { boxWidth: 12 } } },
      },
    });
  }

  if (document.getElementById("purposeChart")) {
    new Chart(document.getElementById("purposeChart"), {
      type: "bar",
      data: {
        labels: data.purpose.map((r) => r.purpose),
        datasets: [
          {
            data: data.purpose.map((r) => r.total),
            backgroundColor: "#d5a45e",
            borderRadius: 4,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } },
      },
    });
  }

  if (document.getElementById("lifecycleChart")) {
    const stages = { Star: [], "Question Mark": [], "Cash Cow": [], Dog: [] };
    data.lifecycle.forEach((d) => {
      if (stages[d.stage])
        stages[d.stage].push({ x: d.share, y: d.growth, name: d.name });
    });

    new Chart(document.getElementById("lifecycleChart"), {
      type: "scatter",
      data: {
        datasets: [
          { label: "Star", data: stages["Star"], backgroundColor: "#198754" },
          {
            label: "Question Mark",
            data: stages["Question Mark"],
            backgroundColor: "#0dcaf0",
          },
          {
            label: "Cash Cow",
            data: stages["Cash Cow"],
            backgroundColor: "#d5a45e",
          },
          { label: "Dog", data: stages["Dog"], backgroundColor: "#dc3545" },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          tooltip: {
            callbacks: {
              label: (ctx) =>
                `${ctx.raw.name}: Share ${ctx.raw.x.toFixed(1)}%, Growth ${ctx.raw.y.toFixed(1)}%`,
            },
          },
        },
        scales: {
          x: { title: { display: true, text: "Market Share (%)" } },
          y: { title: { display: true, text: "YoY Growth (%)" } },
        },
      },
      plugins: [cutoffPlugin],
    });
  }

  if (document.getElementById("forecastChart")) {
    new Chart(document.getElementById("forecastChart"), {
      type: "bar",
      data: {
        labels: data.forecast.map((r) => {
          const m = new Date(2000, r.target_month - 1).toLocaleString(
            "default",
            { month: "short" },
          );
          return `${r.name} (${m} ${r.target_year})`;
        }),
        datasets: [
          {
            data: data.forecast.map((r) => r.forecast),
            backgroundColor: "#d5a45e",
            borderRadius: 4,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } },
      },
    });
  }

  const tbody = document.getElementById("heatmapBody");
  if (tbody && data.heatmap.length > 0) {
    data.heatmap.forEach((dest) => {
      const tr = document.createElement("tr");

      const tdName = document.createElement("td");
      tdName.style.textAlign = "left";
      tdName.style.fontWeight = "500";
      tdName.textContent = dest.name;
      tr.appendChild(tdName);

      if (dest.status === "insufficient_data") {
        const tdEmpty = document.createElement("td");
        tdEmpty.colSpan = 12;
        tdEmpty.textContent = "Not enough data";
        tdEmpty.style.color = "#6c757d";
        tdEmpty.style.fontStyle = "italic";
        tr.appendChild(tdEmpty);
      } else {
        for (let m = 1; m <= 12; m++) {
          const td = document.createElement("td");
          const idx = dest.indices[m];

          let bg = "";
          let text = idx ? idx.toFixed(2) : "-";

          if (idx >= 1.2) bg = "#d5a45e";
          else if (idx >= 0.8) bg = "#fdfbf7";
          else if (idx > 0) bg = "#e9ecef";

          td.style.backgroundColor = bg;
          td.style.fontSize = "12px";
          td.textContent = text;
          tr.appendChild(td);
        }
      }

      const tdDesc = document.createElement("td");
      tdDesc.style.textAlign = "left";
      tdDesc.style.fontSize = "12px";
      tdDesc.style.color = "#495057";
      tdDesc.textContent = dest.desc;
      tr.appendChild(tdDesc);

      tbody.appendChild(tr);
    });
  } else if (tbody) {
    const tr = document.createElement("tr");
    const td = document.createElement("td");
    td.colSpan = 14;
    td.textContent = "No destination demand data available.";
    td.style.textAlign = "center";
    td.style.color = "#6c757d";
    tr.appendChild(td);
    tbody.appendChild(tr);
  }
});
