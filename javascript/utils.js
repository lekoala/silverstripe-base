/* global $, window, jQuery */
var utils = {
    smoothScrolling: function() {
        $("a[href^='#']").click(function(e) {
            e.preventDefault();
            var dest = $(this).attr('href');
            $('html,body').animate({
                scrollTop: $(dest).offset().top
            }, 'slow');
        });
    }
};

// Init
(function($) {
    var dataUtils = $('body').data('utils');
    var list = dataUtils.split(',');
    $.each(list, function(idx, item) {
        utils[item]();
    })
})(jQuery);
