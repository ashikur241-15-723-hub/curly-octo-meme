(function () {
  const data = window.ET_REPORTS;
  if (!data || typeof Chart === 'undefined') return;

  const catEl = document.getElementById('reportCategoryChart');
  if (catEl && data.category.labels.length) {
    new Chart(catEl, {
      type: 'pie',
      data: {
        labels: data.category.labels,
        datasets: [{
          data: data.category.values,
          backgroundColor: [
            '#1f7a4d', '#2e8b57', '#3cb371', '#66a182', '#8fbc8f',
            '#c0392b', '#e67e22', '#f1c40f', '#2980b9', '#8e44ad', '#16a085', '#7f8c8d',
          ],
        }],
      },
      options: { plugins: { legend: { position: 'bottom' } } },
    });
  }

  const trendEl = document.getElementById('reportTrendChart');
  if (trendEl) {
    new Chart(trendEl, {
      type: 'line',
      data: {
        labels: data.monthly.labels,
        datasets: [
          { label: 'Income', data: data.monthly.income, borderColor: '#1f7a4d', tension: 0.3 },
          { label: 'Expense', data: data.monthly.expense, borderColor: '#c0392b', tension: 0.3 },
        ],
      },
      options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } },
        scales: { y: { beginAtZero: true } },
      },
    });
  }
})();
