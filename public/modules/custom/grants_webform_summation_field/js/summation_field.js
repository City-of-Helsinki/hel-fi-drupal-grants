// eslint-disable-next-line no-unused-vars
((Drupal, drupalSettings) => {
  Drupal.behaviors.grants_webform_summation_fieldAccessData = {
    attach: function attach() {
      Object.values(drupalSettings.sumFields).forEach(sumField => {
        var sumFieldName = sumField.sumFieldId
        var summationType = sumField.summationType

        if (sumField.fieldName !== undefined) {
          var fieldName = sumField.fieldName
          var columnName = sumField.columnName
          var fieldIDName = 'edit-' + fieldName + '-items'
          var i = 0
          var continueLoop = true

          while (continueLoop) {
            var myEle = document.getElementById(fieldIDName + '-' + i++ + '-' + columnName);
            if(myEle) {
              var eventType = 'change'
              if (summationType === 'euro') {
                eventType = 'keypress'
              }
              myEle.addEventListener(eventType, (event) => {
                var i = 0
                var continueInnerLoop = true
                var sum = 0;

                while (continueInnerLoop) {
                  var myEle = document.getElementById(fieldIDName + '-' + i++ + '-' + columnName);
                  if(myEle) {
                    if (summationType === 'euro') {
                      myString = 0 + myEle.value.replace(/\D/g,'');
                    } else {
                      myString = 0 + myEle.value
                    }
                    sum += parseInt(myString)
                  } else {
                    continueInnerLoop = false
                  }
                }
                if (summationType === 'euro') {
                  var decimal = (sum % 100).toString();
                  while (decimal.length < 2) {
                    decimal = "0" + decimal;
                  }
                  document.getElementById(sumFieldName).innerHTML = Math.floor(sum / 100) + ',' + decimal + '€'
                } else {
                  document.getElementById(sumFieldName).innerHTML = sum + ''
                }
              });
            } else {
              continueLoop = false
            }
          }
        } else {
          var fieldArray = sumField.fields
          var i = 0
          fieldArray.forEach(fieldName => {
            var myEle = document.getElementById('edit-' + fieldName);
            if(myEle) {
              var eventType = 'change'
              if (summationType === 'euro') {
                eventType = 'keypress'
              }
              myEle.addEventListener(eventType, (event) => {
                var sum = 0
                fieldArray.forEach(item => {
                  if (summationType === 'euro') {
                    myString = 0 + document.getElementById('edit-' + item).value.replace(/\D/g,'');
                  } else {
                    myString = 0 + document.getElementById('edit-' + item).value
                  }
                  sum += parseInt(myString)
                })

                if (summationType === 'euro') {
                  var decimal = (sum % 100).toString();
                  while (decimal.length < 2) {
                    decimal = "0" + decimal;
                  }
                  document.getElementById(sumFieldName).innerHTML = Math.floor(sum / 100) + ',' + decimal + '€'
                } else {
                  document.getElementById(sumFieldName).innerHTML = sum + ''
                }
              })
            }
          })
        }
      })

    },
  };
  // eslint-disable-next-line no-undef
})(Drupal, drupalSettings);
