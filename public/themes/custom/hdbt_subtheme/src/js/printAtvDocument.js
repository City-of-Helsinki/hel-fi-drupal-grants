document.addEventListener(
  'DOMContentLoaded',
  () => {
    const body = document.body;
    body.classList.add('webform-submission-data-preview-page');
    body.classList.add('webform-print');
    (() => {
      window.print();
      setTimeout(() => {
        history.back();
      }, 500);
    })();
  },
  false,
);
