(() => {
  const dialog = document.createElement('dialog');
  dialog.className = 'dialog delete-dialog';
  dialog.setAttribute('aria-labelledby', 'deleteDialogTitle');
  dialog.innerHTML = `
    <div class="dialog-head">
      <div class="delete-dialog-copy">
        <span class="delete-dialog-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24"><path d="M4 7h16M10 11v6m4-6v6M5 7l1 14h12l1-14M9 7V4h6v3"/></svg>
        </span>
        <h2 id="deleteDialogTitle">Excluir produto?</h2>
        <p class="delete-dialog-message"></p>
      </div>
      <button type="button" class="close-button" aria-label="Fechar">×</button>
    </div>
    <div class="dialog-actions">
      <button type="button" class="button secondary cancel-delete">Cancelar</button>
      <button type="button" class="button confirm-delete">Excluir produto</button>
    </div>`;
  document.body.append(dialog);

  let pendingForm = null;
  const close = () => dialog.close();
  dialog.querySelector('.close-button').addEventListener('click', close);
  dialog.querySelector('.cancel-delete').addEventListener('click', close);
  dialog.addEventListener('click', (event) => {
    if (event.target === dialog) close();
  });
  dialog.querySelector('.confirm-delete').addEventListener('click', () => {
    if (!pendingForm) return;
    const form = pendingForm;
    pendingForm = null;
    dialog.close();
    form.requestSubmit();
  });

  document.addEventListener('click', (event) => {
    const button = event.target.closest('.product-actions .danger-text');
    if (!button) return;
    event.preventDefault();
    pendingForm = button.closest('form');
    if (!pendingForm) return;
    const productName = pendingForm.closest('.product-card')?.querySelector('.product-title-row h3')?.textContent?.trim();
    dialog.querySelector('.delete-dialog-message').textContent = productName
      ? `O produto “${productName}” será removido do estoque. Esta ação não poderá ser desfeita.`
      : 'Este produto será removido do estoque. Esta ação não poderá ser desfeita.';
    dialog.showModal();
  });
})();
