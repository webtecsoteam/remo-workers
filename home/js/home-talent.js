function setTalentFilter(btn, filterId) {
  const wrap = document.getElementById('home-talent-filters');
  if (wrap) {
    wrap.querySelectorAll('.tf').forEach(b => b.classList.remove('on'));
    if (btn) btn.classList.add('on');
  }
  const grid = document.getElementById('home-talent-grid');
  if (!grid) return;
  const id = filterId || 'all';
  let visible = 0;
  grid.querySelectorAll('.tc[data-talent-categories]').forEach(card => {
    const cats = (card.getAttribute('data-talent-categories') || '').split(',').filter(Boolean);
    const show = id === 'all' || cats.includes(id);
    card.style.display = show ? '' : 'none';
    if (show) visible++;
  });
  const empty = document.getElementById('home-talent-empty');
  if (empty) {
    empty.style.display = visible === 0 ? 'block' : 'none';
  }
}

function initHomeTalentCards() {
  const grid = document.getElementById('home-talent-grid');
  if (!grid) return;

  grid.querySelectorAll('.tc[data-profile-url]').forEach(card => {
    const url = card.getAttribute('data-profile-url');
    if (!url) return;
    const go = (e) => {
      if (e.target.closest('.tc-save, .tc-btn, a')) return;
      window.location.href = url;
    };
    card.addEventListener('click', go);
    card.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        go(e);
      }
    });
  });

  const filters = document.getElementById('home-talent-filters');
  if (filters) {
    filters.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-talent-filter]');
      if (!btn) return;
      setTalentFilter(btn, btn.getAttribute('data-talent-filter') || 'all');
    });
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initHomeTalentCards);
} else {
  initHomeTalentCards();
}
