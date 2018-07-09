(function ($) {
    $(function () {

        function error($el) {
            $el.addClass('error');
            return true;
        }

        function valid($el) {
            $el.removeClass('error');
        }

        $('form.validator').on('submit', function (e) {
            var hasErrors = false;

            $(this).find('.required').each(function () {
                var $holder = $(this);

                if ($holder.hasClass('optionset')) {
                    if ($holder.find(':checked').length == 0) {
                        hasErrors = error($holder);
                    } else {
                        valid($holder);
                    }
                } else {
                    if ($holder.find('input').val() == '') {
                        hasErrors = error($holder);
                    } else {
                        valid($holder);
                    }
                }
            });

            if (hasErrors) {
                e.preventDefault();

                var $elementWithErrors = $(this).find('.error');

                $('html, body').animate({
                    scrollTop: $elementWithErrors.offset().top - 100
                }, 500);
            }
        });
    });
})(jQuery);
