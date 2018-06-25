/**
 * Tweaks for the CMS
 */
(function ($) {
    // We can't do that because modal are not globally exposed as jquery plugins :-(
    $('#Form_ItemEditForm').on('click', '.inline-action[data-modal]', function (e) {
        e.preventDefault();
        var $this = $(this);
        var options = $this.data('modalConfig');
        $($this.attr('href')).modal(options);
    }).error(function (err) {});
})(jQuery);
