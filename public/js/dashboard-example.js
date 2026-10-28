document.querySelector('#demo-form')?.addEventListener('submit', (event) => {
    const button = event.currentTarget.querySelector('[type="submit"]');
    button.disabled = true;
    button.textContent = button.dataset.loadingLabel;
});
