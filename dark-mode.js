document.addEventListener('DOMContentLoaded', function() {
  const btn = document.getElementById('dark-mode-toggle');
  if (!btn) return;

  const getSystemPref = () => window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
  const saved = localStorage.getItem('dark-mode');
  const initial = saved ? saved === 'on' : getSystemPref();

  const setMode = (on) => {
    document.body.classList.toggle('dark-mode', on);
    btn.setAttribute('aria-pressed', on);
    btn.textContent = on ? '🌞' : '🌓';
  };

  setMode(initial);

  btn.addEventListener('click', function() {
    const isDark = document.body.classList.toggle('dark-mode');
    localStorage.setItem('dark-mode', isDark ? 'on' : 'off');
    setMode(isDark);
  });

  // Update when system theme changes and user has no explicit choice
  if (!saved && window.matchMedia) {
    const mq = window.matchMedia('(prefers-color-scheme: dark)');
    mq.addEventListener('change', e => setMode(e.matches));
  }
});
