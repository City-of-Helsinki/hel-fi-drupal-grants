// eslint-disable-next-line no-unused-vars
((Drupal, drupalSettings) => {
  Drupal.behaviors.grants_budget_total_fieldAccessData = {
    attach: function attach() {
      Object.values(drupalSettings.totalFields).forEach(totalField => {
        const totalFieldName = totalField.totalFieldId;

        let fieldsArray = [];

        let fieldArray = totalField.fields;

        fieldArray.forEach(fieldNameArray => {
          const fieldNameRaw = fieldNameArray.fieldName;
          const fieldName = fieldNameRaw.replaceAll('_', '-');
          const columnNameRaw = fieldNameArray.columnName;
          const columnName = columnNameRaw.toLowerCase();
          fieldsArray.push('edit-' + fieldName + '-' + columnName);
        })

        function calculateTotal() {
          let sum = 0;
          fieldsArray.forEach(item => {
            const elementItem = document.getElementById(item);
            let myString = '';

            if (!elementItem || !elementItem.value) {
              return;
            } else {
              let elementValueRaw = elementItem.value;
              let elementValue = elementValueRaw.replaceAll(',', '.')
              myString = 0 + elementValue;
              myString = myString * 100;
              sum += parseInt(myString);
            }
          })

          sum = sum / 100;
          document.getElementById(totalFieldName).value = sum + '';
        }

        calculateTotal();

        fieldsArray.forEach(field => {
          let myEle = document.getElementById(field);
          if (!myEle) {
            return;
          } else {
            myEle.addEventListener('keyup', (event) => {
              calculateTotal();
              var event = new Event('change');
              document.getElementById(totalFieldName).dispatchEvent(event);
            })
          }
        })
      })

    }
  }
  // eslint-disable-next-line no-undef
})(Drupal, drupalSettings);
