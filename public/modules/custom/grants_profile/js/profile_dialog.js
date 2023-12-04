// eslint-disable-next-line no-unused-vars
(($, Drupal, drupalSettings) => {
  var unsaved = false;
  /**
   * Unsaved changes.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for unsaved changes.
   */
  Drupal.behaviors.profileFormUnsaved = {
    attach: function (context) {
      var initial_name = $('#edit-companynamewrapper-companyname').val();
      var is_element_click = false;
      $('form').submit(function() {
        window.onbeforeunload = null
        is_element_click = true;
      });
      $('a').on('click', function (event) {
        is_element_click = true;
        var current_name = $('#edit-companynamewrapper-companyname').val();
        var unset_name = false
        if (current_name == '') {
          unset_name = true
        }

        let containingElement = document.querySelector('form');
        if ((unset_name) && !containingElement.contains(event.target)) {
          event.preventDefault();
          const $previewDialog = $(
            `<div></div>`,
          ).appendTo('body');
          Drupal.dialog($previewDialog, {
            title: Drupal.t('You need to have a name for your unregistered community or group. Please add a name and save or cancel them.'),
            width: '33%',
            buttons: [
              {
                text: Drupal.t('Back to profile'),
                buttonType: 'primary',
                click() {
                  $(this).dialog('close');
                },
              },
            ],
          }).showModal();
        } else if ((current_name != initial_name) && !containingElement.contains(event.target)) {
          event.preventDefault();
          const $previewDialog = $(
            `<div></div>`,
          ).appendTo('body');
          Drupal.dialog($previewDialog, {
            title: Drupal.t('You have unsaved changes in your profile. Please save or cancel them.'),
            width: '33%',
            buttons: [
              {
                text: Drupal.t('Back to profile'),
                buttonType: 'primary',
                click() {
                  $(this).dialog('close');
                },
              },
            ],
          }).showModal();
        }
        // eslint-disable-next-line no-undef
      })
      window.onbeforeunload = function(event) {
        // Cancel the event as stated by the standard.
        // Chrome requires returnValue to be set.
        let containingElement = document.querySelector('form');

        var current_name = $('#edit-companynamewrapper-companyname').val();
        var unset_name = false
        if (current_name == '') {
          unset_name = true
        }
        if (unset_name && !containingElement.contains(event.target) && !is_element_click) {
          event.preventDefault();
          event.returnValue = Drupal.t('You need to have a name for your unregistered community or group. Are you sure you want to leave the form?');
        }
        if ((current_name != initial_name) && !containingElement.contains(event.target) && !is_element_click) {
          event.preventDefault();
          event.returnValue = Drupal.t('You have unsaved changes in your profile. Are you sure you want to leave the form?');
        }
        is_element_click = false;
      };
    }
  }
})(jQuery, Drupal, drupalSettings);
