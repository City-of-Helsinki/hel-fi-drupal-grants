(function ($, Drupal) {
  // How many pages are shown on each side of the current page.
  const pagerWindow = 2;

  const initPager = function (list) {
    const $container = $(list.listContainer);
    const $pagerContainer = $container.find('.hds-pagination-container');
    const $pages = $container.find('.application-list__pagination');
    const $prev = $container.find('.hds-pagination__button-prev');
    const $next = $container.find('.hds-pagination__button-next');

    const currentPage = function () {
      return Math.ceil(list.i / list.page);
    };

    const totalPages = function () {
      return Math.ceil(list.matchingItems.length / list.page);
    };

    const showPage = function (pageNumber) {
      list.show((pageNumber - 1) * list.page + 1, list.page);
    };

    const pageItem = function (pageNumber, isActive) {
      const $link = $('<a href="#" class="page hds-pagination__item-link"></a>')
        .attr({
          'data-i': pageNumber,
          'aria-label': Drupal.t('Page', {}, { context: 'Pagination page number aria-label' }) + ' ' + pageNumber,
        })
        .text(pageNumber);

      if (isActive) {
        $link
          .addClass('hds-pagination__item-link--active')
          .attr({ 'aria-current': 'page', title: Drupal.t('Current page', {}, { context: 'Pagination current page title' }) });
      }
      else {
        $link.attr('title', Drupal.t('Go to page @key', { '@key': pageNumber }));
      }

      return $('<li class="pager__item"></li>').toggleClass('is-active active', isActive).append($link);
    };

    const ellipsisItem = function () {
      return $('<li class="pager__item pager__item--ellipsis" role="presentation"><span class="hds-pagination__item-ellipsis">&hellip;</span></li>');
    };

    const updatePager = function () {
      const current = currentPage();
      const total = totalPages();
      const first = Math.max(1, current - pagerWindow);
      const last = Math.min(total, current + pagerWindow);

      $pagerContainer.prop('hidden', total < 2);
      $prev.prop('disabled', current <= 1);
      $next.prop('disabled', current >= total);

      $pages.empty();
      if (first > 1) {
        $pages.append(pageItem(1, false));
        if (first > 2) {
          $pages.append(ellipsisItem());
        }
      }
      for (let pageNumber = first; pageNumber <= last; pageNumber++) {
        $pages.append(pageItem(pageNumber, pageNumber === current));
      }
      if (last < total) {
        if (last < total - 1) {
          $pages.append(ellipsisItem());
        }
        $pages.append(pageItem(total, false));
      }
    };

    $pages.on('click', 'a.page', function (e) {
      e.preventDefault();
      showPage(Number($(this).attr('data-i')));
    });
    // Move focus to the first item of the new page so keyboard and screen
    // reader users land on the content that changed.
    const focusFirstItem = function () {
      $container.find('.application-list__item').first()
        .attr('tabindex', '-1')
        .trigger('focus');
    };

    $prev.on('click', function () {
      showPage(currentPage() - 1);
      focusFirstItem();
    });
    $next.on('click', function () {
      showPage(currentPage() + 1);
      focusFirstItem();
    });

    list.on('updated', updatePager);
    updatePager();
  };

  Drupal.behaviors.omaAsiointiFront = {
    attach: function (context, settings) {
      if (!$('#oma-asiointi__sent .application-list__item').length) {
        return;
      }

      if ($("#oma-asiointi__sent")[0]) {
        const sentListOptions = {
          valueNames: ['card__title', 'application-list__item--status', 'application-list__item--number', 'application-list__item--submitted'],
          page: 5,
        };
        const sentList = new List('oma-asiointi__sent', sentListOptions);
        initPager(sentList);
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
              return item.values()['application-list__item--status'] == $('#string-processed').text()
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

    const draftContainer = context.querySelector('#oma-asiointi__drafts');
    const hasDraftItem = draftContainer && draftContainer.querySelector('li.application-list__item.draft') !== null;

    if (hasDraftItem) {
      const draftsListOptions = {
        valueNames: ['card__title', 'application-list__item--status', 'application-list__item--number', 'application-list__item--submitted'],
        page: 5,
      };
      const draftsList = new List('oma-asiointi__drafts', draftsListOptions);
      initPager(draftsList);
    }
  }
};
})(jQuery, Drupal);
