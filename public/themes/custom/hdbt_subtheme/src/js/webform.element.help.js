/**
 * @file
 * JavaScript behaviors for element help text (tooltip).
 */

(($, Drupal, once) => {



  // @see https://atomiks.github.io/tippyjs/v5/all-props/
  // @see https://atomiks.github.io/tippyjs/v6/all-props/
  Drupal.webform = Drupal.webform || {};
  Drupal.webform.elementHelpIcon = Drupal.webform.elementHelpIcon || {};
  Drupal.webform.elementHelpIcon.options = Drupal.webform.elementHelpIcon.options || {};

  /**
   * Element help icon.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformElementHelpIcon = {
    attach(context) {
      if (!window.tippy) {
        return;
      }

      // Hide on escape.
      // @see https://atomiks.github.io/tippyjs/v6/plugins/#hideonesc
      //
      // Converted from ES6 to ES5.
      // @see https://babeljs.io/repl/
      const hideOnEsc = {
        name: 'hideOnEsc',
        defaultValue: true,
        fn: function fn(_ref) {
          const { hide } = _ref;

          function onKeyDown(event) {
            if (event.keyCode === 27) {
              hide();
            }
          }

          return {
            onShow: function onShow() {
              document.addEventListener('keydown', onKeyDown);
            },
            onHide: function onHide() {
              document.removeEventListener('keydown', onKeyDown);
            },
          };
        },
      };

      $(once('webform-element-help', '.js-webform-element-help', context)).each(
        () => {
          const $link = $(this);

          $link.on('click', (event) => {
            // Prevent click from toggling <label>s wrapped around help.
            event.preventDefault();
          });

          const options = $.extend(
            {
              content: $link.attr('data-webform-help'),
              delay: 100,
              allowHTML: true,
              interactive: false,
              trigger: 'click',
              plugins: [hideOnEsc],
            },
            Drupal.webform.elementHelpIcon.options,
          );

          window.tippy(this, options);
        });
    },
  };

})(jQuery, Drupal, once);
