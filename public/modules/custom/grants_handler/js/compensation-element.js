(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.GrantsHandlerCompensationElement = {
    attach: function (context, settings) {

      const subventionElement = document.querySelector('#edit-subventions');
      const foundTypes = subventionElement.querySelectorAll('input[name*="[subventionType]"]');

      const elemParents = Array.from(foundTypes).reduce(function(acc, value) {
        const tr = value.closest('tr');
        acc[value.value] = {
          tr: tr,
          input: tr.querySelector('input[name*="[amount]"]')
        };
        return acc;
      }, {});

      // If we ask for specific type (Like starttiavustus).
      if (subventionElement.dataset.questionSubtypeId) {
        const subventionId = subventionElement.dataset.questionSubtypeId;
        elemParents[subventionId].input.dataset.isQuestionLocked = true;
        let buttons = Drupal.behaviors.GrantsHandlerCompensationElement.createRadiobuttons(
          subventionElement.dataset.questionSubventionStrings
        );
        $(subventionElement).prepend(buttons);
        Drupal.behaviors.GrantsHandlerCompensationElement.validateQuestionStates(
          elemParents,
          subventionId,
          buttons
        );
        Drupal.behaviors.GrantsHandlerCompensationElement.addEventListenerToRadios(
          buttons,
          elemParents,
          subventionId,
          subventionElement.dataset.questionSubventionInputValue
        )
      }

      // Only allow one subvention type.
      if (subventionElement.dataset.singleSubventionType === '1') {

        // Event listeners
        for (let [key, value] of Object.entries(elemParents)) {
          value.input.addEventListener('keyup', (e) => {
            const cleanValue = e.target.value.replace('€', '');
            if (cleanValue === '0.00' || cleanValue === '0,00' || cleanValue === null || cleanValue === '') {
              Drupal.behaviors.GrantsHandlerCompensationElement.enableAll(elemParents);
            } else if (cleanValue !== '') {
              Drupal.behaviors.GrantsHandlerCompensationElement.disableOthers(key, elemParents);
            }
          })
        }

        // Validate state after pageload.
        Drupal.behaviors.GrantsHandlerCompensationElement.validateElementStates(elemParents)

      }
    },
    disableOthers: function(trigger, elements) {
      for (let [key, value] of Object.entries(elements)) {
        if (key === trigger) {
          continue;
        }

        value.input.value = '';
        value.input.setAttribute('readonly', true)
      }
    },
    enableAll: function(elements) {
      for (let [key, value] of Object.entries(elements)) {
        if (value.input.dataset.isQuestionLocked) {
          continue;
        }
        value.input.removeAttribute('readonly')
      }
    },
    validateElementStates: function(elements) {
      for (let [key, value] of Object.entries(elements)) {
      const cleanValue = value.input.value;
      if (cleanValue != false) {
        Drupal.behaviors.GrantsHandlerCompensationElement.disableOthers(key, elements);
        break;
      }
      }
    },
    validateQuestionStates: function(elements, questionSubtypeId, buttons) {
      const subTypeHasValue = (
        elements[questionSubtypeId].input.value != false
      );
      let otherFieldHasValue = false;
      for (let [key, value] of Object.entries(elements)) {
        if (key === questionSubtypeId) {
          continue;
        } else if (value.input.value != false) {
          otherFieldHasValue = true;
          break;
        }
      }

      if (subTypeHasValue) {
        Drupal.behaviors.GrantsHandlerCompensationElement.disableOthers(questionSubtypeId, elements);
        elements[questionSubtypeId].input.setAttribute('readonly', true);
        $(buttons).find('#compensation-yes').attr('checked', true);
      } else if (otherFieldHasValue) {
        $(buttons).find('#compensation-no').attr('checked', true);
        elements[questionSubtypeId].input.setAttribute('readonly', true);
        elements[questionSubtypeId].input.value = '';
      } else {
        $(buttons).find('#compensation-no').attr('checked', false);
        $(buttons).find('#compensation-yes').attr('checked', false);
      }
    },
    createRadiobuttons: function(strings) {
      strings = JSON.parse(strings);
      var container = $('<div class="form-item">');

      var label = $(`<legend class="fieldset-legend">${strings.question_text}*</legend>`)

      // Create and append the radio buttons
      var option1 = $(`<div class="js-form-item form-item js-form-type-radio hds-radio-button">
      <input type="radio" id="compensation-yes" name="compensation-q" value="1" required class="form-radio hds-radio-button__input" required="required">
      <label for="compensation-yes" class="option hds-radio-button__label">${strings.yes}</label>`);

      var option2 = $(`<div class="js-form-item form-item js-form-type-radio hds-radio-button">
      <input type="radio" id="compensation-no" name="compensation-q" value="0" class="form-radio hds-radio-button__input" required="required">
      <label for="compensation-no" class="option hds-radio-button__label">${strings.no}</label>`);

      container.append(label, option1, option2);

      return container;
    },
    addEventListenerToRadios: function(buttons, elements, subventionId, inputValue) {
      $(buttons).find('#compensation-yes').on('change', (event) => {
        Drupal.behaviors.GrantsHandlerCompensationElement.disableOthers(subventionId, elements);
        elements[subventionId].input.value = inputValue;
        elements[subventionId].input.dispatchEvent(new Event('change'))
        elements[subventionId].input.setAttribute('readonly', true);
      })

      $(buttons).find('#compensation-no').on('change', (event) => {
        Drupal.behaviors.GrantsHandlerCompensationElement.enableAll(elements);
        elements[subventionId].input.value = '';
        elements[subventionId].input.setAttribute('readonly', true);
        elements[subventionId].input.dispatchEvent(new Event('change'))
      })
    }
  };
})(jQuery, Drupal, drupalSettings);
