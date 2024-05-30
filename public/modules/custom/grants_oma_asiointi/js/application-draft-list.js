(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.omaAsiointiFront = {
    attach: function (context, settings) {
      if ($("#oma-asiointi__sent")[0]) {
        const sentListOptions = {
          valueNames: ['application-list__item--name', 'application-list__item--status', 'application-list__item--number', 'application-list__item--submitted'],
          pagination: true,
          page: 10,
        };
        const sentList = new List('oma-asiointi__sent', sentListOptions);
        $('#oma-asiointi__sent .application-list__count-value').html(sentList.update().matchingItems.length);

        sentList.on('searchComplete', function () {
          $('#oma-asiointi__sent .application-list__count-value').html(sentList.update().matchingItems.length);
        });

        $('#searchForApplication').click(function() {
          const searchValue = $('#applicationListFilter').val();
          sentList.search(searchValue);
        });

        $('#applicationListFilter').on('keypress', function(e) {
          if (e.which == 13) {
            const searchValue = $('#applicationListFilter').val();
            sentList.search(searchValue);
          }
        });

        $('select.sort').change(function () {
          const selectionArray = $(this).val().split(' ');
          const selection = selectionArray[1];
          const direction = selectionArray[0]
          sentList.sort(selection, {order: direction});
        });

        $('button.sort').click(function () {
          sentList.sort($(this).data('sort'));
        });
        sentList.sort('application-list__item--submitted', {order: 'desc'});

        $('#checkbox-processed').change(function() {
          if(this.checked) {
            sentList.filter(function(item) {
              if (item.values()['application-list__item--status'] == $('#string-processed').text()) {
                return true;
              } else {
                return false;
              }
            }); // Only items with id > 1 are shown in list
          } else {
          sentList.filter(function(item) {
            return true;
          });
          sentList.filter();
          }
          $('#oma-asiointi__sent .application-list__count-value').html(sentList.update().matchingItems.length);

        });
    }
  }
};
})(jQuery, Drupal, drupalSettings);
