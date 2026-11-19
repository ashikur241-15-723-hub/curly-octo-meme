(function () {
  const typeSelect = document.getElementById('transaction_type');
  const catSelect = document.getElementById('category_id');
  if (!typeSelect || !catSelect) return;

  function parseList(attr) {
    try {
      return JSON.parse(catSelect.getAttribute(attr) || '[]');
    } catch (e) {
      return [];
    }
  }

  function refreshCategories() {
    const type = typeSelect.value === 'income' ? 'income' : 'expense';
    const list = parseList(type === 'income' ? 'data-income' : 'data-expense');
    const selected = catSelect.getAttribute('data-selected') || catSelect.value;
    catSelect.innerHTML = '';
    if (!list.length) {
      const opt = document.createElement('option');
      opt.value = '';
      opt.textContent = 'No categories — add some first';
      catSelect.appendChild(opt);
      return;
    }
    list.forEach((item) => {
      const opt = document.createElement('option');
      opt.value = String(item.id);
      opt.textContent = item.name;
      if (String(item.id) === String(selected)) opt.selected = true;
      catSelect.appendChild(opt);
    });
  }

  typeSelect.addEventListener('change', () => {
    catSelect.setAttribute('data-selected', '');
    refreshCategories();
  });
  refreshCategories();
})();
