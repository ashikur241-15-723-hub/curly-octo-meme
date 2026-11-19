(function () {
  const root = document.documentElement;
  const stored = localStorage.getItem('et-theme');
  if (stored === 'dark' || stored === 'light') {
    root.setAttribute('data-bs-theme', stored);
  }

  function toggleTheme() {
    const next = root.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
    root.setAttribute('data-bs-theme', next);
    localStorage.setItem('et-theme', next);
    updateThemeIcon();
  }

  function updateThemeIcon() {
    const btn = document.getElementById('themeToggle');
    if (!btn) return;
    const dark = root.getAttribute('data-bs-theme') === 'dark';
    btn.innerHTML = dark ? '<i class="bi bi-sun"></i>' : '<i class="bi bi-moon-stars"></i>';
  }

  document.getElementById('themeToggle')?.addEventListener('click', toggleTheme);
  updateThemeIcon();

  const sidebar = document.getElementById('appSidebar');
  const backdrop = document.getElementById('sidebarBackdrop');
  function closeSidebar() {
    sidebar?.classList.remove('open');
    backdrop?.classList.remove('show');
  }
  document.getElementById('sidebarToggle')?.addEventListener('click', () => {
    sidebar?.classList.add('open');
    backdrop?.classList.add('show');
  });
  document.getElementById('sidebarClose')?.addEventListener('click', closeSidebar);
  backdrop?.addEventListener('click', closeSidebar);

  document.getElementById('pdfExportBtn')?.addEventListener('click', function () {
    const tableSel = this.getAttribute('data-table');
    const title = this.getAttribute('data-title') || 'ExpenseTrack Report';
    const filename = this.getAttribute('data-filename') || 'expensetrack.pdf';
    const table = document.querySelector(tableSel);
    if (!table || !window.jspdf) {
      alert('PDF export is unavailable in this browser.');
      return;
    }
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ orientation: 'landscape', unit: 'pt', format: 'a4' });
    doc.setFontSize(14);
    doc.text('ExpenseTrack', 40, 36);
    doc.setFontSize(11);
    doc.text(title, 40, 54);
    doc.setFontSize(9);
    doc.text('Generated: ' + new Date().toLocaleString(), 40, 70);

    const rows = [];
    const headers = [];
    table.querySelectorAll('thead th').forEach((th) => {
      if (!th.classList.contains('print-hide')) headers.push(th.innerText.trim());
    });
    table.querySelectorAll('tbody tr').forEach((tr) => {
      const cells = [];
      tr.querySelectorAll('td').forEach((td, idx) => {
        if (td.classList.contains('print-hide')) return;
        // Skip action column if last header was dropped
        if (headers.length && idx >= headers.length) return;
        cells.push(td.innerText.trim());
      });
      if (cells.length) rows.push(cells.slice(0, headers.length));
    });

    doc.autoTable({
      head: [headers],
      body: rows,
      startY: 84,
      styles: { fontSize: 8 },
      headStyles: { fillColor: [31, 122, 77] },
    });
    doc.save(filename);
  });
})();
