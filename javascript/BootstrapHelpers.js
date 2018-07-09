(function ($, Cookies) {
    $(function () {
        // Handle alerts that can be dismissed
        $('.js-alert').on('closed.bs.alert', function () {
            var id = $(this).data('id');
            var dismissed = Cookies.getJSON('DismissedAlerts');
            if (!dismissed) {
                dismissed = [];
            }
            dismissed.push(id);
            Cookies.set('DismissedAlerts', dismissed);
        });
        // Tabs improvements
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            // history.pushState({}, '', e.target.hash);
        });
        var hash = document.location.hash;
        var prefix = "tab_";
        if (hash) {
            $('.nav-tabs a[href="' + hash.replace(prefix, "") + '"]').tab('show');
        }
    });
})(jQuery, Cookies);
