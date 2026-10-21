/* One native-menu controller: separate parent links, disclosure, keyboard and responsive mega panel. */
(function () {
  'use strict';
  var header = document.querySelector('.site-header');
  var toggle = header && header.querySelector('.nav-toggle');
  var panel = document.getElementById('primary-nav');
  if (!header || !toggle || !panel) return;
  var desktop = window.matchMedia('(min-width:901px)');
  var hover = window.matchMedia('(hover:hover) and (pointer:fine)');
  var items = [];
  var language = document.querySelector('.site-language');
  var languageOrigin = document.getElementById('eq-language-origin');
  var mobileLanguage = document.getElementById('eq-mobile-language');
  var utilityLinks = document.querySelector('.eq-utility-links');
  var utilityOrigin = document.querySelector('.eq-utility-actions');
  function setOpen(item, open) {
    item.node.classList.toggle('is-submenu-open', open);
    item.button.setAttribute('aria-expanded', String(open));
    if (!open) items.forEach(function (child) {
      if (child !== item && item.node.contains(child.node)) {
        child.node.classList.remove('is-submenu-open');
        child.button.setAttribute('aria-expanded', 'false');
      }
    });
  }
  function megaChild(item) { return desktop.matches && item.node.classList.contains('menu-family') && item.node.closest('.equipment-menu'); }
  function openSubmenu(item) {
    if (megaChild(item)) return;
    items.forEach(function (other) { if (other !== item && !other.node.contains(item.node)) setOpen(other, false); });
    items.forEach(function (ancestor) { if (ancestor.node.contains(item.node)) setOpen(ancestor, true); });
    setOpen(item, true);
    if (desktop.matches && !item.node.classList.contains('equipment-menu')) {
      item.node.classList.toggle('submenu-align-right', item.submenu.getBoundingClientRect().right > window.innerWidth - 16);
    }
  }
  function closeMenu() {
    items.forEach(function (item) { setOpen(item, false); });
    panel.classList.remove('open');
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-label', 'Open menu');
  }
  function openMenu() {
    panel.classList.add('open');
    toggle.setAttribute('aria-expanded', 'true');
    toggle.setAttribute('aria-label', 'Close menu');
  }
  panel.querySelectorAll('li').forEach(function (node, index) {
    var submenu = Array.from(node.children).find(function (c) { return c.tagName === 'UL' && c.querySelector('a'); });
    var label = Array.from(node.children).find(function (c) { return c.tagName === 'A' || c.classList.contains('menu-group-label'); });
    if (!submenu || !label) return;
    var button = document.createElement('button');
    button.type = 'button'; button.className = 'submenu-toggle';
    button.setAttribute('aria-expanded', 'false');
    button.setAttribute('aria-label', 'Toggle submenu: ' + label.textContent.trim());
    submenu.id = 'primary-submenu-' + index;
    button.setAttribute('aria-controls', submenu.id);
    node.insertBefore(button, submenu);
    node.classList.add('has-submenu');
    var item = {node:node, submenu:submenu, label:label, button:button};
    items.push(item);
    button.addEventListener('click', function () { if (button.getAttribute('aria-expanded') === 'true') setOpen(item,false); else openSubmenu(item); });
    if (label.tagName !== 'A') label.addEventListener('click', function () { button.click(); });
    node.addEventListener('pointerenter', function () { if (desktop.matches && hover.matches) openSubmenu(item); });
    node.addEventListener('pointerleave', function () { if (desktop.matches && hover.matches && !submenu.contains(document.activeElement) && !megaChild(item)) setOpen(item,false); });
    node.addEventListener('focusout', function (event) { if (desktop.matches && !node.contains(event.relatedTarget)) setOpen(item,false); });
    [label,button].forEach(function (control) {
      control.addEventListener('keydown', function (event) {
        if (event.key !== 'ArrowDown' && event.key !== 'ArrowRight') return;
        event.preventDefault(); openSubmenu(item);
        var first = submenu.querySelector('a'); if (first) first.focus();
      });
    });
    node.addEventListener('keydown', function (event) {
      if (!['Escape','ArrowLeft'].includes(event.key) || button.getAttribute('aria-expanded') !== 'true') return;
      event.preventDefault(); event.stopPropagation(); setOpen(item,false); button.focus();
    });
  });
  panel.classList.add('is-enhanced');
  toggle.addEventListener('click', function () { if (panel.classList.contains('open')) closeMenu(); else openMenu(); });
  panel.addEventListener('click', function (event) { if (event.target.closest('a')) closeMenu(); });
  document.addEventListener('keydown', function (event) { if (event.key !== 'Escape') return; var open=panel.classList.contains('open'); closeMenu(); if (open) toggle.focus(); });
  document.addEventListener('click', function (event) { if (!header.contains(event.target)) closeMenu(); });
  header.addEventListener('focusout', function (event) { if (!header.contains(event.relatedTarget)) closeMenu(); });
  function placeLanguage() {
    if (utilityLinks && utilityOrigin && mobileLanguage) {
      if (desktop.matches) utilityOrigin.insertBefore(utilityLinks, languageOrigin);
      else mobileLanguage.appendChild(utilityLinks);
    }
    if (language && languageOrigin && mobileLanguage) (desktop.matches ? languageOrigin : mobileLanguage).appendChild(language);
  }
  desktop.addEventListener('change', function () { closeMenu(); placeLanguage(); });
  placeLanguage();
  var searchButton = header.querySelector('.eq-search-toggle');
  var searchDialog = document.getElementById('eq-site-search');
  if (searchButton && searchDialog) {
    searchButton.addEventListener('click', function () {
      closeMenu(); searchDialog.showModal();
      var input = searchDialog.querySelector('input[type=search]'); if (input) input.focus();
    });
    searchDialog.querySelector('[data-search-close]').addEventListener('click', function () { searchDialog.close(); });
    searchDialog.addEventListener('click', function (event) { if (event.target === searchDialog) { var box = searchDialog.getBoundingClientRect(); if (event.clientX < box.left || event.clientX > box.right || event.clientY < box.top || event.clientY > box.bottom) searchDialog.close(); } });
    searchDialog.addEventListener('close', function () { var target = searchButton.offsetParent ? searchButton : header.querySelector('.brand a'); if (target) target.focus(); });
    desktop.addEventListener('change', function () { if (desktop.matches && searchDialog.open) searchDialog.close(); });
  }
  function syncLanguage() {
    var select = language && language.querySelector('select.gt_selector');
    document.querySelectorAll('.eq-footer-language').forEach(function (node) { node.hidden = !select; });
    if (!select) return;
    var code = select.value.split('|').pop().toUpperCase() || 'EN';
    document.querySelectorAll('.eq-current-language').forEach(function (node) { node.textContent = code; });
  }
  document.querySelectorAll('.eq-language-jump').forEach(function (button) {
    button.addEventListener('click', function () {
      if (!desktop.matches) openMenu();
      window.scrollTo({top:0,behavior:window.matchMedia('(prefers-reduced-motion:reduce)').matches?'instant':'smooth'});
      var select = language && language.querySelector('select.gt_selector'); if (select) select.focus();
    });
  });
  if (language) {
    new MutationObserver(syncLanguage).observe(language,{childList:true,subtree:true});
    language.addEventListener('change', syncLanguage);
  }
  syncLanguage();
})();
