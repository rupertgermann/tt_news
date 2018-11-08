define(['jquery'], function ($) {

    var ajaxUrl = TYPO3.settings.ajaxUrls['tt_news_backend_module'];

    var NewsBackendModule = {};
    var highlightClass = 'active';

    NewsBackendModule.loadList = function ($element) {
        $.ajax({
            url: ajaxUrl,
            type: 'get',
            dataType: 'html',
            cache: false,
            data: {
                'category': $element.data('category'),
                'id': $element.data('pid'),
                'action': 'loadList'
            }
        }).done(function (response) {
            // Replace content
            $('#' + $element.data('target')).html(response);
            NewsBackendModule.highlightActiveItem($element.data('category'));
        });
    };

    NewsBackendModule.expandCollapse = function ($element) {
        var isExpand = $element.data('isexpand');
        var parent = $element.closest('li');
        var img = parent.find('a.pmiconatag img');

        parent.find('ul').remove();

        $element.data('isexpand', 1);

        if (!isExpand) {
            var src = img.attr('src');
            if (!!src) {
                var newsrc = src.replace('minus', 'plus');
                img.attr('src', newsrc);
            }
        } else {
            $.ajax({
                url: ajaxUrl,
                type: 'get',
                dataType: 'html',
                cache: false,
                data: {
                    'PM': $element.data('params'),
                    'id': $element.data('pid'),
                    'action': 'expandTree'
                }
            }).done(function (response) {
                $element.closest('li').replaceWith(response);
            });
        }
    };


    NewsBackendModule.highlightActiveItem = function (category) {
        var highlightID = '#row' + category + '_0';
        var highlightClass = 'active';

        // Remove all items that are already highlighted
        $('ul#treeRoot li').removeClass(highlightClass);
        // Set the new item
        if (!!$(highlightID)) {
            $(highlightID).addClass(highlightClass);
        }
    };

    NewsBackendModule.initializeEvents = function () {
        var tree = $('#ttnews-cat-tree');
        tree.on('click', '.filter-category', function (evt) {
            evt.preventDefault();
            NewsBackendModule.loadList($(this));
        });
        tree.on('click', '.pmiconatag', function (evt) {
            evt.preventDefault();
            NewsBackendModule.expandCollapse($(this));
        });
    };

    $(NewsBackendModule.initializeEvents);
});

