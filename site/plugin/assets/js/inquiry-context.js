(function () {
  'use strict';
  document.querySelectorAll('form select[name="product"][data-equipment-products]').forEach(function (select) {
    var map;
    try { map = JSON.parse(select.getAttribute('data-equipment-products') || '{}'); } catch (_) { return; }
    var form = select.closest('form');
    var field = form && form.querySelector('input[name="eq_product_id"]');
    if (!field || !map || typeof map !== 'object') return;
    function sync() {
      var id = map[select.value];
      field.value = Number.isInteger(id) && id > 0 ? String(id) : '';
    }
    select.addEventListener('change', sync);
    form.addEventListener('reset', function () { window.requestAnimationFrame(sync); });
    sync();
  });
})();
