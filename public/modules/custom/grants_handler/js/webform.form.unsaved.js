/**
 * @file
 * JavaScript behaviors for unsaved webforms.
 */

(function ($, Drupal, once) {
  /**
   * Dear future developer of this file,
   *
   * This is a javascript file that is imported as an override. This means that
   * any Drupal.t function calls made here will not get passed to drupalTranslations
   * variable, so you need to call those again in a Javascript function that you call
   * in a file that you attach the regular way. In this file's case, that file is
   * webform-additions.js that is used in all the places this override is used
   * in as well.
   *
   * Yours,
   * Past developer.
   */

  'use strict';

  var unsaved = false;
  var modal = false;
  var autologout = false;

  /**
   * Unsaved changes.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for unsaved changes.
   */
  Drupal.behaviors.webformUnsaved = {
    clear: function () {
      // Allow Ajax refresh/redirect to clear unsaved flag.
      // @see Drupal.AjaxCommands.prototype.webformRefresh
      unsaved = false;
    },
    get: function () {
      // Get the current unsaved flag state.
      return unsaved;
    },
    set: function (value) {
      // Set the current unsaved flag state.
      unsaved = value;
    },

    attach: function (context) {
      // Detect general unsaved changes.
      // Look for the 'data-webform-unsaved' attribute which indicates that
      // a multi-step webform has unsaved data.
      // @see \Drupal\webform\WebformSubmissionForm::buildForm
      if ($(once('data-webform-unsaved', '.js-webform-unsaved[data-webform-unsaved]')).length) {
        unsaved = true;
      } else {
        $(once('webform-unsaved', $('.js-webform-unsaved :input:not(:button, :submit, :reset, [type="hidden"])'))).on('change keypress', function (event, param1) {
          // Ignore events triggered when #states API is changed,
          // which passes 'webform.states' as param1.
          // @see webform.states.js ::triggerEventHandlers().
          if (param1 !== 'webform.states') {
            unsaved = true;
          }
        });
      }

      // Detect file uploads.
      $(once('webform-file-upload', '.js-form-managed-file input[type="file"]', context)).on('change', function () {
        unsaved = true;
      });

      // Detect when a file is uploaded via Ajax (when the file appears in the DOM).
      $(once('webform-file-ajax', '.file--mime-application-pdf, .file--mime-application-doc', context)).each(function () {
        unsaved = true;
      });

      $(once('webform-unsaved', $('.js-webform-unsaved button, .js-webform-unsaved input[type="submit"]', context))).not('[data-webform-unsaved-ignore]')
        .on('click', function (event) {
          // For reset button we must confirm unsaved changes before the
          // before unload event handler.
          if ($(this).hasClass('webform-button--reset') && unsaved) {
            if (!window.confirm(Drupal.t('Changes you made may not be saved.') + '\n\n' + Drupal.t('Press OK to leave this page or Cancel to stay.'))) {
              return false;
            }
          }
          unsaved = false;
        });

      // Ensure file changes reset unsaved state after an Ajax submit.
      // Add submit handler to form.beforeSend.
      // Update Drupal.Ajax.prototype.beforeSend only once.
      if (typeof Drupal.Ajax !== 'undefined' && typeof Drupal.Ajax.prototype.beforeSubmitWebformUnsavedOriginal === 'undefined') {
        Drupal.Ajax.prototype.beforeSubmitWebformUnsavedOriginal = Drupal.Ajax.prototype.beforeSubmit;
        Drupal.Ajax.prototype.beforeSubmit = function (form_values, element_settings, options) {
          unsaved = false;
          return this.beforeSubmitWebformUnsavedOriginal.apply(this, arguments);
        };
      }

      // Track all CKEditor change events.
      // @see https://ckeditor.com/old/forums/Support/CKEditor-jQuery-change-event
      if (window.CKEDITOR && !CKEDITOR.webformUnsaved) {
        CKEDITOR.webformUnsaved = true;
        CKEDITOR.on('instanceCreated', function (event) {
          event.editor.on('change', function (evt) {
            unsaved = true;
          });
        });
      }
    }
  };

  $('a').on('click', function (event) {
    let containingElement = document.querySelector('form');
    if (unsaved && !containingElement.contains(event.target) && !event.target.getAttribute('href').startsWith('#')) {
      event.preventDefault();

      return Drupal.dialogFunctions.createDialog(
        Drupal.t('You have unsaved changes. Are you sure you want to leave?', {}, { context: 'grants_handler' }),
        Drupal.t('Leave the application', {}, { context: 'grants_handler' }),
        Drupal.t('Back to application', {}, { context: 'grants_handler' }),
        Drupal.t('Close', {}, { context: 'grants_handler' }),
        () => {
          unsaved = false;
          const dialog = document.getElementById('helfi-dialog__container');
          Drupal.dialogFunctions.removeDialog(dialog);
          window.top.location.href = event.currentTarget.href;
        }
      );
    }
  });

  // Prevent page refresh via keyboard or browser button when unsaved changes are present
  $(window).on('beforeunload', function (event) {
    if (unsaved) {
      // Show a confirmation dialog when the user tries to refresh or leave the page
      const message = Drupal.t('You have unsaved changes. Are you sure you want to leave?', {}, { context: 'grants_handler' });
      event.preventDefault();
      event.returnValue = message; // For most browsers
      return message; // For older browsers
    }
  });

  // Also prevent refresh from keyboard shortcuts (F5, Ctrl+R, Cmd+R)
  $(document).on('keydown', function (e) {
    if (unsaved && (e.which === 116 || (e.which === 82 && (e.ctrlKey || e.metaKey)))) {
      e.preventDefault(); // Prevent F5 and Ctrl+R / Cmd+R refresh

      return Drupal.dialogFunctions.createDialog(
        Drupal.t('You have unsaved changes. Are you sure you want to refresh?', {}, { context: 'grants_handler' }),
        Drupal.t('Refresh the page', {}, { context: 'grants_handler' }),
        Drupal.t('Back to application', {}, { context: 'grants_handler' }),
        Drupal.t('Close', {}, { context: 'grants_handler' }),
        () => {
          unsaved = false;
          const dialog = document.getElementById('helfi-dialog__container');
          Drupal.dialogFunctions.removeDialog(dialog);
          location.reload();
        }
      );
    }
  });

  // Add an event listener for autologout.
  document.addEventListener('autologout', function () {
    autologout = true;
  });

  $(window).on('beforeunload', function () {
    if (autologout) {
      return;
    }
    if (unsaved) {
      return true;
    }
    if (modal) {
      modal = false;
      unsaved = true;
    }
  });

  /**
   * An experimental shim to partially emulate onBeforeUnload on iOS.
   * Part of https://github.com/codedance/jquery.AreYouSure/
   *
   * Copyright (c) 2012-2014, Chris Dance and PaperCut Software http://www.papercut.com/
   * Dual licensed under the MIT or GPL Version 2 licenses.
   * http://jquery.org/license
   *
   * Author:  chris.dance@papercut.com
   * Date:    19th May 2014
   */
  $(function () {
    // @see https://stackoverflow.com/questions/58019463/how-to-detect-device-name-in-safari-on-ios-13-while-it-doesnt-show-the-correct
    var isIOSorOpera = navigator.userAgent.toLowerCase().match(/iphone|ipad|ipod|opera/)
      || navigator.platform.toLowerCase().match(/iphone|ipad|ipod/)
      || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    if (!isIOSorOpera) {
      return;
    }

    $('a:not(.use-ajax)').bind('click', function (evt) {
      var a = $(evt.target).closest('a');
      var href = a.attr('href');
      if (typeof href !== 'undefined' && !(href.match(/^#/) || href.trim() === '')) {
        if ($(window).triggerHandler('beforeunload')) {

          if (!window.confirm(Drupal.t('Changes you made may not be saved.') + '\n\n' + Drupal.t('Press OK to leave this page or Cancel to stay.'))) {
            return false;
          }
        }
        var target = a.attr('target');
        if (target) {
          window.open(href, target);
        } else {
          window.location.href = href;
        }
        return false;
      }
    });
  });

})(jQuery, Drupal, once);
