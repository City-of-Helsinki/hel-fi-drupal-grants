// eslint-disable-next-line no-unused-vars
((Drupal, drupalSettings) => {
  Drupal.behaviors.grants_budget_total_fieldAccessData = {
    attach: function attach() {

      function calculateCostTotal() {
        var sumCost = 0;

        // Loop through the input fields and add their values to the sum

        drupalSettings.costTotalFields.forEach(costTotalField => {

          let costTotalFieldName = 'edit-suunnitellut-menot-' + costTotalField.name
          let costTotalFieldNameLowercase = costTotalFieldName.toLowerCase()

          if (!document.getElementById(costTotalFieldNameLowercase) || !document.getElementById(costTotalFieldNameLowercase).value) {
            return
          }

          let costTotalValue = document.getElementById(costTotalFieldNameLowercase).value
          let costInt = parseInt(costTotalValue)
          sumCost += costInt;
        })

        // Update the result field with the calculated sum
        const totalCostField = document.getElementById('edit-cost-total-cost')
        totalCostField.value = sumCost;
      }

      // Add a keyup event listener to each input field
      drupalSettings.costTotalFields.forEach(costTotalField => {
        let costTotalFieldName = 'edit-suunnitellut-menot-' + costTotalField.name
        let costTotalFieldNameLowercase = costTotalFieldName.toLowerCase()
        let element = document.getElementById(costTotalFieldNameLowercase)
        if (element) {
          element.addEventListener('keyup', calculateCostTotal);
        }
      });

      function calculateIncomeTotal() {
        var sumIncome = 0;

        // Loop through the input fields and add their values to the sum

        drupalSettings.incomeTotalFields.forEach(incomeTotalField => {

          let incomeTotalFieldName = 'edit-budget-static-income-' + incomeTotalField.name
          let incomeTotalFieldNameLowercase = incomeTotalFieldName.toLowerCase()

          if (!document.getElementById(incomeTotalFieldNameLowercase) || !document.getElementById(incomeTotalFieldNameLowercase).value) {
            return
          }

          let incomeTotalValue = document.getElementById(incomeTotalFieldNameLowercase).value
          let incomeInt = parseInt(incomeTotalValue)
          sumIncome += incomeInt;
        })

        // Update the result field with the calculated sum
        const totalIncomeField = document.getElementById('edit-income-total-income')
        totalIncomeField.value = sumIncome;
      }

      // Add a keyup event listener to each input field
      drupalSettings.incomeTotalFields.forEach(incomeTotalField => {
        let incomeTotalFieldName = 'edit-budget-static-income-' + incomeTotalField.name
        let incomeTotalFieldNameLowercase = incomeTotalFieldName.toLowerCase()
        let elementIncome = document.getElementById(incomeTotalFieldNameLowercase)
        if (elementIncome) {
          elementIncome.addEventListener('keyup', calculateIncomeTotal);
        }
      });

    }
  }
  // eslint-disable-next-line no-undef
})(Drupal, drupalSettings);
