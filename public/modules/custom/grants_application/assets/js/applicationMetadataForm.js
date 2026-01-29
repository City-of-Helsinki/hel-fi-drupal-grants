/**
 * @file
 * Autofill "readonly" application metadata fields based on a select choice.
 */
((Drupal, drupalSettings, once) => {
  Drupal.behaviors.applicationMetadataForm = {
    attach(context) {
      const selects = once(
        'applicationTypeSelect',
        'select[name="application_type_select"]',
        context
      );
      const types = drupalSettings?.grants_application?.application_types || {};

      /**
       * Resolve display + value fields for a given application type id.
       */
      const resolveValues = (id) => {
        const entry = types?.[id];
        const label =
          (entry &&
            typeof entry === 'object' &&
            entry.labels &&
            entry.labels.fi) ||
          '';
        const typeValue =
          (entry && typeof entry === 'object' && entry.code) || '';
        const typeIdValue = entry.id || '';
        const identifier = entry.form_identifier || '';
        return { label, typeValue, typeIdValue, identifier };
      };

      /**
       * Return the inputs for the application metadata.
       */
      const getFields = (root) => {
        const labelInput = root.querySelector('input[name="label[0][value]"]');
        const typeInput = root.querySelector('input[name="application_type[0][value]"]');
        const typeIdInput = root.querySelector('input[name="application_type_id[0][value]"]');
        const formIdentifierInput = root.querySelector('input[name="form_identifier[0][value]"]')
        return { labelInput, typeInput, typeIdInput, formIdentifierInput };
      };

      /**
       * Apply values into the target input fields.
       */
      const apply = (fields, id) => {
        const { labelInput, typeInput, typeIdInput, identifierInput } = fields;

        // If a field is missing, do nothing for this select instance.
        if (!(labelInput && typeInput && typeIdInput)) {
          return;
        }

        const isValid =
          id && id !== '_none' && Object.prototype.hasOwnProperty.call(types, id);

        if (!isValid) {
          labelInput.value = '';
          typeInput.value = '';
          typeIdInput.value = '';
          identifierInput.value = '';
          return;
        }

        const { label, typeValue, typeIdValue, name } = resolveValues(id);
        labelInput.value = label;
        typeInput.value = typeValue;
        typeIdInput.value = typeIdValue;
        identifierInput.value = name;
      };

      // Bind change handlers for each select.
      selects.forEach((select) => {
        // Get the scope for the select.
        const scope = select.closest('form') || context;
        const fields = getFields(scope);

        // Initial sync on attach (covers prefilled selects).
        apply(fields, select.value);

        // Update on changes.
        select.addEventListener('change', (e) => {
          apply(fields, e.currentTarget.value);
        });
      });
    },
  };
})(Drupal, drupalSettings, once);
