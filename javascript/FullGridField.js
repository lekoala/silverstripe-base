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
            onclick: function (e) {
                // Prevent row click
                e.stopPropagation();
                // Check/uncheck checkbox when clicking cell
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
