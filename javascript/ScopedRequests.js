/**
 * All links with data-scope will be handled
 *
 * Use applicationResponse in php to properly respond to these requests
 */
(function ($) {
    $(document).on('click', 'a[data-scope]', function (e) {
        e.preventDefault();

        var $this = $(this);
        var $scope = $($this.data('scope'));

        $.getJSON($this.attr('href'), function (result) {
            var messageType = 'success';
            if (!result.success) {
                messageType = 'error';
            }
            if (result.message) {
                alertify.notify(result.message, messageType, 0);
            }
            if (result.manipulations) {
                for (var i = 0; i < result.manipulations.length; i++) {
                    var manipulation = result.manipulations[i];
                    var el = $scope;
                    if (manipulation.selector) {
                        el = $scope.find(manipulation.selector);
                        if (!el.length) {
                            console.log(manipulation.selector + ' did not match anything in scope ' + $this.data('scope'));
                        }
                    }
                    if (el.length && manipulation.action) {
                        if (manipulation.html) {
                            el[manipulation.action](manipulation.html);
                        } else {
                            el[manipulation.action]();
                        }
                    }
                }
            }
        });
    });
})(jQuery);
