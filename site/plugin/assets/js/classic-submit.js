/* global document, HTMLFormElement, setTimeout */
(() => {
  'use strict';
  const form = document.getElementById('post');
  if (!(form instanceof HTMLFormElement) || typeof form.requestSubmit !== 'function') return;

  function deferFirstSave(event) {
    const submitter = event.submitter;
    if (event.defaultPrevented || !submitter || submitter.form !== form) return;
    if (!['publish', 'save-post'].includes(submitter.id)) return;
    if (document.getElementById('wp-preview')?.value === 'dopreview') return;
    event.preventDefault();
    event.stopImmediatePropagation();
    form.removeEventListener('submit', deferFirstSave, true);
    // A JS task keeps Rank Math's resolved promises outside the active native
    // submission event. requestSubmit retains HTML and ACF validation.
    setTimeout(() => form.requestSubmit(submitter), 0);
  }

  form.addEventListener('submit', deferFirstSave, true);
})();
