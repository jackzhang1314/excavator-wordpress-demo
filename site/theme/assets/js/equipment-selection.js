import {displayMeasurement} from './equipment-units.js';

const root = document.querySelector('.equipment-category');
if (root) {
  const controls = root.querySelector('[data-equipment-tools]');
  const dialog = root.querySelector('[data-compare-dialog]');
  const choices = [...root.querySelectorAll('[data-compare-product]')];
  const key = 'equipment-comparison-v1';
  let items = [], system = 'metric', opener;
  const safeItem = item => item && Number.isInteger(item.id) && item.id > 0
    && typeof item.title === 'string' && typeof item.family === 'string'
    && typeof item.url === 'string' && new URL(item.url, location.href).origin === location.origin
    && Array.isArray(item.specs) && item.specs.every(row => row && typeof row.label === 'string' && typeof row.value === 'string');
  try {
    const saved = JSON.parse(sessionStorage.getItem(key) || '[]');
    if (Array.isArray(saved)) items = saved.filter(safeItem).filter((item, i, rows) => rows.findIndex(row => row.id === item.id) === i).slice(0, 3);
    system = sessionStorage.getItem('equipment-unit-system') === 'imperial' ? 'imperial' : 'metric';
  } catch { /* Selection remains usable for this page when storage is unavailable. */ }
  function save() { try { sessionStorage.setItem(key, JSON.stringify(items)); } catch { /* Private browsing. */ } }
  function units() {
    root.querySelectorAll('[data-equipment-value]').forEach(node => { node.textContent = displayMeasurement(node.dataset.equipmentValue, system); });
    root.querySelectorAll('[data-unit]').forEach(button => { button.setAttribute('aria-pressed', String(button.dataset.unit === system)); });
    root.querySelectorAll('.equipment-filter [data-equipment-option]').forEach(option => { option.textContent = displayMeasurement(option.dataset.equipmentOption, system); });
    try { sessionStorage.setItem('equipment-unit-system', system); } catch { /* No storage. */ }
  }
  function update() {
    choices.forEach(choice => { const item = JSON.parse(choice.dataset.compareProduct); choice.checked = items.some(row => row.id === item.id); });
    const open = controls?.querySelector('[data-compare-open]');
    if (open) { open.textContent = 'Compare (' + items.length + ')'; open.disabled = items.length < 2; }
    const status = controls?.querySelector('[data-compare-status]');
    if (status) status.textContent = items.length ? items.length + ' of 3 selected. Choose at least two models.' : 'Select up to 3 models to compare.';
    save();
  }
  function element(tag, text, className) {
    const node = document.createElement(tag);
    if (text !== undefined) node.textContent = text;
    if (className) node.className = className;
    return node;
  }
  function render() {
    const content = dialog.querySelector('[data-compare-content]');
    content.replaceChildren();
    const table = element('table'), head = element('thead'), body = element('tbody'), tr = element('tr');
    tr.append(element('th', 'Model / requirement'));
    items.forEach(item => {
      const th = element('th'); th.scope = 'col';
      const link = element('a', item.title); link.href = item.url; th.append(link, element('p', item.family));
      const remove = element('button', 'Remove'); remove.type = 'button'; remove.setAttribute('aria-label', 'Remove ' + item.title + ' ' + item.family);
      remove.addEventListener('click', () => { items = items.filter(row => row.id !== item.id); update(); if (items.length < 2) dialog.close(); else render(); });
      th.append(remove); tr.append(th);
    });
    head.append(tr);
    const labels = [...new Set(items.flatMap(item => item.specs.map(row => row.label)))];
    labels.forEach(label => {
      const row = element('tr'), th = element('th', label); th.scope = 'row'; row.append(th);
      items.forEach(item => { const value = item.specs.find(spec => spec.label === label)?.value || 'Not supplied'; row.append(element('td', displayMeasurement(value, system))); });
      body.append(row);
    });
    table.append(head, body); content.append(table);
  }
  choices.forEach(choice => {
    choice.closest('[data-compare-choice]').hidden = false;
    choice.addEventListener('change', () => {
      const item = JSON.parse(choice.dataset.compareProduct);
      if (choice.checked && items.length >= 3) {
        choice.checked = false;
        controls.querySelector('[data-compare-status]').textContent = 'Three models are selected. Remove one to choose another.';
        return;
      }
      items = items.filter(row => row.id !== item.id);
      if (choice.checked && safeItem(item)) items.push(item);
      update();
    });
  });
  if (controls && dialog) {
    controls.hidden = false;
    controls.querySelectorAll('[data-unit]').forEach(button => button.addEventListener('click', () => { system = button.dataset.unit; units(); }));
    controls.querySelector('[data-compare-open]').addEventListener('click', event => {
      if (items.length < 2) return;
      opener = event.currentTarget; render(); dialog.showModal(); dialog.querySelector('[data-compare-close]').focus();
    });
    dialog.querySelector('[data-compare-close]').addEventListener('click', () => dialog.close());
    dialog.addEventListener('close', () => opener?.focus());
    dialog.addEventListener('click', event => { if (event.target === dialog) { const box = dialog.getBoundingClientRect(); if (event.clientX < box.left || event.clientX > box.right || event.clientY < box.top || event.clientY > box.bottom) dialog.close(); } });
  }
  const filterForm = root.querySelector('[data-equipment-auto-filter]');
  if (filterForm) {
    const apply = filterForm.querySelector('[data-equipment-apply]');
    if (apply) apply.hidden = true;
    filterForm.addEventListener('change', event => {
      if (event.target instanceof HTMLInputElement && event.target.type === 'radio') filterForm.requestSubmit();
    });
  }
  units(); update();
}
