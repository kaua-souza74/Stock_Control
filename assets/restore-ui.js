(() => {
  const forms = document.querySelectorAll('form[method="post"]');
  forms.forEach((form) => form.addEventListener('submit', () => {
    form.querySelectorAll('.scroll-position').forEach((input) => {
      input.value = String(window.scrollY);
    });
  }));

  const restoreScroll = document.documentElement.dataset.restoreScroll;
  if (restoreScroll !== undefined && restoreScroll !== '') {
    window.addEventListener('load', () => {
      requestAnimationFrame(() => window.scrollTo(0, Number(restoreScroll)));
    }, { once: true });
  }

  document.querySelectorAll('.toast-notice').forEach((toast) => {
    if (toast.dataset.persistent === 'true') return;
    window.setTimeout(() => { toast.classList.add('toast-dismiss'); window.setTimeout(() => toast.remove(), 220); }, 2000);
  });
})();
