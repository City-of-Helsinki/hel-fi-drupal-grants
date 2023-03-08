(function (Drupal, drupalSettings) {
  Drupal.behaviors.GrantsHandlerApplicationStatusCheck = {
    attach: function (context, settings) {
      var pollFrequency = 5000
      var maxFrequency = 300000
      var timerInterval = null
      var applicationNumber = null
      var requestUrl = null
      var currentStatus = null
      var statusTagElement = null

      function changeValue() {
        stop()
        pollFrequency = Math.min(maxFrequency, pollFrequency * 2)
        const xhttp = new XMLHttpRequest()
        if (statusTagElement !== null) {
          statusTagElement.classList.remove("hide-spinner")
        }
        var dataJson = currentStatus;
        xhttp.onload = function() {
          data = this.responseText
          try {
            dataJson = JSON.parse(data).data.value
          } catch (e) {
            statusTagElement.classList.add("show-error")
          }

          if (dataJson !== currentStatus && Object.keys(JSON.parse(data).data).length > 0) {
            location.reload()
          }
          if (statusTagElement !== null) {
            statusTagElement.classList.add("hide-spinner")
          }
          start(pollFrequency)
        }
        xhttp.open("GET", requestUrl)
        xhttp.send()

      }

      function start(timeoutValue) {
        stop() // stoping the previous counting (if any)
        timerInterval = setInterval(changeValue, timeoutValue)
      }
      var stop = function() {
        clearInterval(timerInterval)
      }
      var onlyOneCheckable = document.getElementsByClassName('grants-handler__completion')

      if (onlyOneCheckable.length == 1) {
        statusTagElement = document.getElementsByClassName('application-list__item--status')[0]
        applicationNumber = statusTagElement.getAttribute('data-application-number')
        requestUrl = drupalSettings.grants_handler.site_url + 'grants-metadata/status-check/' + applicationNumber
        currentStatus = statusTagElement.getAttribute('data-status')
        start(pollFrequency)
      }
    }
  };
})(Drupal, drupalSettings);
