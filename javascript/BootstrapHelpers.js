(function ($) {
    $(function () {
        $('.js-alert').on('closed.bs.alert', function () {
            var id = $(this).data('id');
            var dismissed = Cookies.getJSON('DismissedAlerts');
            if (!dismissed) {
                dismissed = [];
            }
            dismissed.push(id);
            Cookies.set('DismissedAlerts', dismissed);
        });
    });
})(jQuery);
