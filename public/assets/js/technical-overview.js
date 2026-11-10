(function () {
  "use strict";

  var root = document.getElementById("technical-overview");
  if (!root || typeof window.Chart !== "function") return;

  var palette = {
    brand: "#8d1a28",
    green: "#0f8a4d",
    amber: "#a9660b",
    muted: "#9aa1ab",
    grid: "#e4e7ec",
    text: "#6b7280",
  };
  var programPalette = [
    "#8d1a28", "#0f8a4d", "#a9660b", "#3569a8",
    "#7356a5", "#16838a", "#c14f75", "#687382",
  ];
  var numberFormat = new Intl.NumberFormat();

  function parseData(canvas, name, fallback) {
    try {
      var value = canvas.getAttribute("data-chart-" + name);
      return value ? JSON.parse(value) : fallback;
    } catch (error) {
      console.error("Unable to read Technical Admin chart data:", error);
      return fallback;
    }
  }

  function setupActivity(canvas) {
    var labels = parseData(canvas, "labels", []);
    var logins = parseData(canvas, "logins", []);
    var failed = parseData(canvas, "failed", []);
    if (!labels.length || labels.length !== logins.length || labels.length !== failed.length) {
      console.error("Sign-in activity chart data is incomplete or inconsistent.");
      return;
    }

    new Chart(canvas, {
      type: "line",
      data: {
        labels: labels,
        datasets: [
          {
            label: "Accounts signed in",
            data: logins,
            borderColor: palette.brand,
            backgroundColor: function (context) {
              var area = context.chart.chartArea;
              if (!area) return "rgba(141, 26, 40, 0.12)";
              var gradient = context.chart.ctx.createLinearGradient(0, area.top, 0, area.bottom);
              gradient.addColorStop(0, "rgba(141, 26, 40, 0.22)");
              gradient.addColorStop(1, "rgba(141, 26, 40, 0.015)");
              return gradient;
            },
            pointBackgroundColor: "#fff",
            pointBorderColor: palette.brand,
            pointBorderWidth: 2,
            pointRadius: 2.5,
            pointHoverRadius: 5,
            borderWidth: 2.5,
            tension: 0.38,
            fill: true,
          },
          {
            label: "Failed attempts",
            data: failed,
            borderColor: palette.amber,
            backgroundColor: "rgba(169, 102, 11, 0.04)",
            pointBackgroundColor: "#fff",
            pointBorderColor: palette.amber,
            pointBorderWidth: 2,
            pointRadius: 2.5,
            pointHoverRadius: 5,
            borderWidth: 2,
            tension: 0.38,
            fill: false,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 450 },
        interaction: { mode: "index", intersect: false },
        layout: { padding: { top: 8, right: 8 } },
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: "#1f2937",
            titleColor: "#fff",
            bodyColor: "#f3f4f6",
            padding: 11,
            cornerRadius: 8,
            displayColors: true,
            callbacks: {
              title: function (items) {
                var index = items[0] && items[0].dataIndex;
                var label = labels[index];
                return Array.isArray(label) ? label.join(" · ") : label;
              },
              label: function (item) {
                return " " + item.dataset.label + ": " + numberFormat.format(item.raw);
              },
            },
          },
        },
        scales: {
          x: {
            grid: { display: false },
            border: { display: false },
            ticks: {
              color: palette.text,
              font: { size: 10, weight: "600" },
              maxRotation: 0,
              minRotation: 0,
              callback: function (value, index) {
                var label = labels[index];
                return Array.isArray(label) ? label[0] : label;
              },
            },
          },
          y: {
            beginAtZero: true,
            grace: "8%",
            border: { display: false, dash: [3, 4] },
            grid: { color: palette.grid, drawTicks: false },
            ticks: {
              color: palette.text,
              precision: 0,
              padding: 9,
              font: { size: 10 },
              callback: function (value) { return numberFormat.format(value); },
            },
          },
        },
      },
    });
  }

  function setupDoughnut(canvas, kind) {
    var labels = parseData(canvas, "labels", []);
    var values = parseData(canvas, "values", []);
    var total = Number(canvas.getAttribute("data-chart-total"));
    var colors = kind === "program"
      ? parseData(canvas, "colors", programPalette)
      : [palette.green, palette.amber, palette.muted];

    if (!labels.length || labels.length !== values.length || !Number.isFinite(total) || total <= 0) {
      console.error("Distribution chart data is incomplete or inconsistent.");
      return;
    }

    new Chart(canvas, {
      type: "doughnut",
      data: {
        labels: labels,
        datasets: [{
          data: values,
          backgroundColor: colors,
          hoverBackgroundColor: colors,
          borderColor: "#fff",
          borderWidth: 3,
          hoverOffset: 8,
          borderRadius: 3,
          spacing: 2,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: "70%",
        animation: { duration: 550 },
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: "#1f2937",
            titleColor: "#fff",
            bodyColor: "#f3f4f6",
            padding: 11,
            cornerRadius: 8,
            displayColors: true,
            callbacks: {
              label: function (item) {
                var value = Number(item.raw) || 0;
                var percentage = total ? (value / total) * 100 : 0;
                return " " + numberFormat.format(value) + " (" + percentage.toFixed(1) + "%)";
              },
            },
          },
          technicalCenterText: {
            total: total,
            caption: kind === "program" ? "students" : "accounts",
          },
        },
      },
      plugins: [{
        id: "technicalCenterText",
        afterDraw: function (chart, args, options) {
          var chartArea = chart.chartArea;
          if (!chartArea) return;

          var points = chart.tooltip && chart.tooltip.dataPoints;
          var point = points && points.length ? points[0] : null;
          var value = point ? Number(point.raw) || 0 : options.total;
          var percentage = options.total ? (value / options.total) * 100 : 0;
          var primary = point
            ? numberFormat.format(value) + " · " + percentage.toFixed(1) + "%"
            : numberFormat.format(options.total);
          var secondary = point ? String(point.label) : options.caption;
          var context = chart.ctx;
          var centerX = (chartArea.left + chartArea.right) / 2;
          var centerY = (chartArea.top + chartArea.bottom) / 2;

          context.save();
          context.textAlign = "center";
          context.textBaseline = "middle";
          context.fillStyle = "#1f2937";
          context.font = "700 20px system-ui, -apple-system, Segoe UI, sans-serif";
          context.fillText(primary, centerX, centerY - 7, chartArea.width * 0.72);
          context.fillStyle = palette.text;
          context.font = "500 11px system-ui, -apple-system, Segoe UI, sans-serif";
          context.fillText(secondary, centerX, centerY + 15, chartArea.width * 0.72);
          context.restore();
        },
      }],
    });
  }

  function setupQueue(canvas) {
    var labels = parseData(canvas, "labels", []);
    var values = parseData(canvas, "values", []);
    if (
      !labels.length
      || labels.length !== values.length
      || values.some(function (value) { return !Number.isFinite(Number(value)) || Number(value) < 0; })
    ) {
      console.error("Operational queue chart data is incomplete or inconsistent.");
      return;
    }

    new Chart(canvas, {
      type: "bar",
      data: {
        labels: labels,
        datasets: [{
          label: "Records",
          data: values,
          backgroundColor: [
            "rgba(141, 26, 40, 0.82)",
            "rgba(53, 105, 168, 0.82)",
            "rgba(15, 138, 77, 0.78)",
            "rgba(169, 102, 11, 0.8)",
            "rgba(193, 79, 117, 0.78)",
            "rgba(22, 131, 138, 0.8)",
          ],
          borderRadius: 5,
          borderSkipped: false,
          barThickness: 18,
          maxBarThickness: 22,
        }],
      },
      options: {
        indexAxis: "y",
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 450 },
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: "#1f2937",
            titleColor: "#fff",
            bodyColor: "#f3f4f6",
            padding: 11,
            cornerRadius: 8,
            callbacks: {
              label: function (item) { return " " + numberFormat.format(item.raw) + " records"; },
            },
          },
        },
        scales: {
          x: {
            beginAtZero: true,
            grace: "8%",
            border: { display: false },
            grid: { color: palette.grid, drawTicks: false },
            ticks: {
              color: palette.text,
              precision: 0,
              padding: 8,
              font: { size: 10 },
              callback: function (value) { return numberFormat.format(value); },
            },
          },
          y: {
            border: { display: false },
            grid: { display: false },
            ticks: {
              color: palette.text,
              font: { size: 10, weight: "600" },
              autoSkip: false,
            },
          },
        },
      },
    });
  }

  root.querySelectorAll("canvas[data-technical-chart]").forEach(function (canvas) {
    var kind = canvas.getAttribute("data-technical-chart");
    if (kind === "activity") setupActivity(canvas);
    if (kind === "status" || kind === "program") setupDoughnut(canvas, kind);
    if (kind === "queue") setupQueue(canvas);
  });
})();
