(function ($) {
    $.entwine('ss', function ($) {
        /**
         * Bulkselect table cell behaviours
         */
        $('td.col-FullGridSelect').entwine({
            onmatch: function () {
            },
            onunmatch: function () {
            },
            onmouseover: function () {
                //disable default row click behaviour -> avoid navigation to edit form when clicking the checkbox
                $(this).parents('.ss-gridfield-item').find('.edit-link').removeClass('edit-link').addClass('tempDisabledEditLink');
            },
            onmouseout: function () {
                //re-enable default row click behaviour
                $(this).parents('.ss-gridfield-item').find('.tempDisabledEditLink').addClass('edit-link').removeClass('tempDisabledEditLink');
            },
            onclick: function (e) {
                //check/uncheck checkbox when clicking cell
                var cb = $(e.target).find('input');
                if (!$(cb).prop('checked'))
                    $(cb).prop('checked', true);
                else
                    $(cb).prop('checked', false);
            }
        });

        /**
         * Fuzzy search on cells
         */
        $('input.FullGridFieldQuickFilter').entwine({
            onmatch: function () {
            },
            onunmatch: function () {
            },
            onfocusout: function () {
                this.doFilter();
            },
            onkeyup: function () {
                this.doFilter();
            },
            doFilter: function () {
                var table = this.parents('fieldset').find('table.grid-field__table');
                var val = this.val().toLowerCase();
                table.find('tbody tr').each(function (i, item) {
                    var text = item.textContent.toLowerCase();
                    item.style.display = text.indexOf(val) === -1 ? 'none' : 'table-row';
                });
            }
        });


    });
}(jQuery));
