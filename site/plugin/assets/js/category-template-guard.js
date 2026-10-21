/* ACF swaps complete field groups asynchronously. Keep unsaved content in its active editor. */
(() => {
  const initialize = () => {
    const control = document.querySelector('#page_template');
    if (!(control instanceof HTMLSelectElement)) return;
    let previous = control.value;
    control.addEventListener('change', event => {
      if (control.value === previous) return;
      // ACF's own unload model also observes rich text, media and repeater changes.
      if (window.acf?.unload?.changed) {
        control.value = previous;
        event.preventDefault();
        event.stopImmediatePropagation();
        let notice = document.querySelector('#category-template-save-notice');
        if (!notice) {
          notice = document.createElement('p');
          notice.id = 'category-template-save-notice';
          notice.className = 'notice notice-warning inline';
          notice.setAttribute('role', 'alert');
          control.parentElement.append(notice);
        }
        notice.textContent = 'Save your current category content before switching templates. Your unsaved changes are still on this page.';
        return;
      }
      previous = control.value;
    }, true);
  };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, { once: true });
  else initialize();
})();
