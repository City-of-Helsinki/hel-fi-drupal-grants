/**
 * @file
 * JavaScript behaviors for webform wizard pages.
 */

(($, Drupal, once) => {


  /**
   * Link the wizard's previous pages.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Links the wizard's previous pages.
   */
  Drupal.behaviors.webformWizardPagesLink = {
    attach(context) {
      $(once('webform-wizard-pages-links', '.js-webform-wizard-pages-links', context)).each(() => {
        const $pages = $(this);
        const $form = $pages.closest('form');

        const hasProgressLink = $pages.data('wizard-progress-link');
        const hasPreviewLink = $pages.data('wizard-preview-link');
        let index = 0;
        $pages.find('.js-webform-wizard-pages-link').each(() => {
          const $button = $(this);
          // @todo fix this to abide by linter rules if react rework gets canceled
          // eslint-disable-next-line no-plusplus
          index++;

          const page = $button.data('webform-page');
          const title = `${index  }/${  $('.js-webform-wizard-pages-links .js-webform-wizard-pages-link').length + 1 }: ${  $button.attr('title')}`;

          // Link progress marker and title.
          if (hasProgressLink) {
            const $progress = $form.find(`.webform-progress [data-webform-page="${  page  }"]`);
            $progress.find('.progress-marker, .progress-title, .webform-progress-bar__page-title')
              .attr({
                'role': 'link',
                'title': title,
                'aria-label': title,
                'tabindex': '0'
              })
              .on('click', () => {
                $button.trigger('click');
              })
              .on('keydown', (event) => {
                if (event.which === 13) {
                  $button.trigger('click');
                }
              });
            // Only allow the marker to be tabbable.
            $progress.find('.progress-marker, .webform-progress-bar__page-title').attr('tabindex', 0);
          }

          // Move button to preview page div container with [data-webform-page].
          // @see \Drupal\webform\Plugin\WebformElement\WebformWizardPage::formatHtmlItem
          if (hasPreviewLink) {
            $form
              .find(`.webform-preview [data-webform-page="${  page  }"]`)
              .append($button)
              .show();
          }

        });
      });
    }
  };

})(jQuery, Drupal, once);
