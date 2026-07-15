document.addEventListener(
  'DOMContentLoaded',
  () => {
    document.body.classList.add('webform-submission-data-preview-page', 'webform-print');
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        window.print();
        setTimeout(() => window.history.back(), 500);
      });
    });
  },
  false,
);
