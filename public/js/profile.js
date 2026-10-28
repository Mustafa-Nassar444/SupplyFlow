(() => {
    const modal = document.querySelector('#confirm-user-deletion');
    if (!modal) return;
    modal.addEventListener('shown.bs.modal', () => modal.querySelector('[name="password"]').focus());
    if (modal.dataset.openOnError === 'true') window.tabler.Modal.getOrCreateInstance(modal).show();
})();
