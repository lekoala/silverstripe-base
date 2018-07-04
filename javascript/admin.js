/**
 * Tweaks for the CMS
 */
(function ($) {
    var updateTarget = function (el, forceUpdate) {
        var data = el.data('map');
        var target = el.data('target');
        if (!data || !target) {
            console.log("Empty data or no target");
            return;
        }
        var val = el.val();
        var input = $('#Form_ItemEditForm_' + target);
        if (input.val() && !forceUpdate) {
            return;
        }
        if (data[val]) {
            input.val(data[val]).trigger('change').trigger("chosen:updated").trigger("liszt:updated");
        }
    }

    $.entwine('ss', function ($) {
        // Allow select with a data map attribute to update other selects
        $('select[data-map]').entwine({
            onmatch: function () {
                this._super();
                updateTarget(this, false);
            },
            onchange: function () {
                updateTarget(this, true);
            }
        });
    });

    // Prevent submit with enter
    $(document).on("keypress", ":input:not(textarea)", function (event) {
        if (event.keyCode == 13) {
            event.preventDefault();
            // This is just lazy excel like tab
            $(this).parents('.field').next('.field').find(':input').first().focus();
        }
        return true;
    });

})(jQuery);
