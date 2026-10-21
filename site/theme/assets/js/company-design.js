(() => {
  const documentSort = document.querySelector('.eq-document-sort select');
  documentSort?.addEventListener('change', () => documentSort.form?.requestSubmit());
  const industryNav = document.querySelector('.eq-industry-nav');
  if (industryNav?.classList.contains('eq-industry-task-nav')) {
    const taskLinks = [...industryNav.querySelectorAll('a')];
    const activateTask = () => {
      const active = taskLinks.find(link => link.hash === location.hash) || taskLinks[0];
      taskLinks.forEach(link => { if (link === active) link.setAttribute('aria-current', 'true'); else link.removeAttribute('aria-current'); });
    };
    activateTask();
    window.addEventListener('hashchange', activateTask);
  } else if (industryNav) {
    const links = [...industryNav.querySelectorAll('a')];
    const targets = links.map(link => document.getElementById(link.hash.slice(1)));
    let queued = false;
    const update = () => {
      const header = document.querySelector('.site-header');
      const headerThreshold = (header?.getBoundingClientRect().bottom || 0) + 30;
      const scrollPadding = parseFloat(getComputedStyle(document.documentElement).scrollPaddingTop) || 0;
      let active = 0;
      targets.forEach((target, index) => {
        if (!target) return;
        const scrollMargin = parseFloat(getComputedStyle(target).scrollMarginTop) || 0;
        const threshold = Math.max(headerThreshold, scrollPadding + scrollMargin + 2, window.innerHeight * .3);
        if (target.getBoundingClientRect().top <= threshold) active = index;
      });
      links.forEach((link, index) => { if (index === active) link.setAttribute('aria-current', 'true'); else link.removeAttribute('aria-current'); });
      queued = false;
    };
    update();
    window.addEventListener('scroll', () => { if (!queued) { queued = true; requestAnimationFrame(update); } }, {passive:true});
    window.addEventListener('resize', update);
  }
  document.querySelectorAll('[data-faq-topics]').forEach(root => {
    const links = [...root.querySelectorAll('.eq-topic-nav a')];
    const panels = [...root.querySelectorAll('[data-faq-topic]')];
    if (!links.length || !panels.length) return;
    const activate = id => {
      const chosen = panels.find(panel => panel.id === id) || panels[0];
      panels.forEach(panel => { panel.hidden = panel !== chosen; });
      links.forEach(link => {
        if (link.hash === '#' + chosen.id) link.setAttribute('aria-current', 'true');
        else link.removeAttribute('aria-current');
      });
    };
    links.forEach(link => link.addEventListener('click', event => {
      if (!panels.some(panel => '#' + panel.id === link.hash)) return;
      event.preventDefault();
      activate(link.hash.slice(1));
      history.replaceState(null, '', link.hash);
    }));
    activate(location.hash.slice(1));
    window.addEventListener('hashchange', () => activate(location.hash.slice(1)));
  });
})();
