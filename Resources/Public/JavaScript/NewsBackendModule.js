define(['jquery'], function($) {

    var ajaxUrl = TYPO3.settings.ajaxUrls['tt_news_backend_module'];

    var NewsBackendModule = {};
    var highlightClass = 'active';

    NewsBackendModule.loadList = function($element) {
        $.ajax({
            url: ajaxUrl,
            type: 'get',
            dataType: 'html',
            cache: false,
            data: {
                'category': $element.data('category'),
                'pid': $element.data('pid'),
                'action': 'loadList'
            }
        }).done(function(response) {
            // Replace content
            $('#' + $element.data('target')).html(response);
            NewsBackendModule.highlightActiveItem($element.data('category'));
        });
    };

    NewsBackendModule.highlightActiveItem = function(category) {
        var highlightID = '#row' + category + '_0';
        // Remove all items that are already highlighted
        $('ul#treeRoot li').removeClass(highlightClass);
        // Set the new item
        if (!! $(highlightID)) {
            $(highlightID).addClass(highlightClass);
        }
    };

    NewsBackendModule.initializeEvents = function() {
        // Click event to change permissions
        $('#ttnews-cat-tree').on('click', '.filter-category', function(evt) {
            evt.preventDefault();
            NewsBackendModule.loadList($(this));
        });
    };

    $(NewsBackendModule.initializeEvents);
});

