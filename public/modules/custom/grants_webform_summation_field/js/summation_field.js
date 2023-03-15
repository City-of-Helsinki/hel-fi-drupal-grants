// eslint-disable-next-line no-unused-vars
((Drupal, drupalSettings) => {
  Drupal.behaviors.grants_webform_summation_fieldAccessData = {
    attach: function attach() {
      Object.values(drupalSettings.sumFields).forEach(sumField => {
        const sumFieldName = sumField.sumFieldId
        const summationType = sumField.summationType
        let isMultipleField = false
        if (sumField.fieldName !== undefined) {
          isMultipleField = true
        }
        let fieldsArray = []
        if (isMultipleField) {
          const fieldName = sumField.fieldName
          const columnName = sumField.columnName
          const fieldIDName = 'edit-' + fieldName + '-items'
          let i = 0
          let continueLoop = true

          while (continueLoop) {
            const myEle = document.getElementById(fieldIDName + '-' + i + '-' + columnName)
            if (myEle) {
              fieldsArray.push(fieldIDName + '-' + i++ + '-' + columnName)
            }
            else {
              continueLoop = false
            }
          }
        }
        else {
          let fieldArray = sumField.fields
          let i = 0
          fieldArray.forEach(fieldName => {
            fieldsArray.push('edit-' + fieldName)
          })

        }

        fieldsArray.forEach(field => {
          let myEle = document.getElementById(field.replaceAll('_', '-'))
          let eventType = 'change'
          if ((myEle.tagName.toLowerCase() === 'input' && (
              myEle.getAttribute('type').toLowerCase() == 'text'
              || myEle.getAttribute('type').toLowerCase() == 'number'))
            || myEle.tagName === 'textarea'.toLowerCase()) {
            eventType = 'keypress'
          }
          myEle.addEventListener(eventType, (event) => {
            let sum = 0
            fieldsArray.forEach(item => {
              const elementItem = document.getElementById(item.replaceAll('_', '-'))
              let myString = ''
              if (summationType === 'euro') {
                myString = 0 + elementItem.value.replace(/\D/g, '');
              }
              else {
                myString = 0 + elementItem.value
              }
              sum += parseInt(myString)
            })
            if (summationType === 'euro') {
              let decimal = (sum % 100).toString();
              while (decimal.length < 2) {
                decimal = "0" + decimal;
              }
              document.getElementById(sumFieldName).innerHTML = Math.floor(sum / 100) + ',' + decimal + '€'
            }
            else {
              document.getElementById(sumFieldName).innerHTML = sum + ''
            }
          })
        })
      })
    }
  }
  // eslint-disable-next-line no-undef
})(Drupal, drupalSettings);
