(function () {
  const data = window.ET_DASHBOARD;
  if (!data || typeof Chart === 'undefined') return;

  const textColor = getComputedStyle(document.documentElement).getPropertyValue('--et-text').trim() || '#1a2b22';
  Chart.defaults.color = textColor;
  Chart.defaults.borderColor = 'rgba(127,127,127,0.2)';

  const monthlyEl = document.getElementById('monthlyChart');
  if (monthlyEl) {
    new Chart(monthlyEl, {
      type: 'bar',
      data: {
        labels: data.monthly.labels,
        datasets: [
          { label: 'Income', data: data.monthly.income, backgroundColor: 'rgba(31,122,77,0.75)' },
          { label: 'Expense', data: data.monthly.expense, backgroundColor: 'rgba(192,57,43,0.7)' },
        ],
      },
      options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } },
        scales: { y: { beginAtZero: true } },
      },
    });
  }

  const catEl = document.getElementById('categoryChart');
  if (catEl && data.category.labels.length) {
    new Chart(catEl, {
      type: 'doughnut',
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
      options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } },
      },
    });
  }

  const dailyEl = document.getElementById('dailyChart');
  if (dailyEl) {
    new Chart(dailyEl, {
      type: 'line',
      data: {
        labels: data.daily.labels,
        datasets: [{
          label: 'Daily expense',
          data: data.daily.values,
          borderColor: '#c0392b',
          backgroundColor: 'rgba(192,57,43,0.15)',
          fill: true,
          tension: 0.3,
        }],
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } },
      },
    });
  }
})();
