(function ($) {
    $('input.flatpickr').on('moduleBeforeInit', function (event, config) {
        var $this = $(this);
        var plugins = [];
        if ($this.data('range')) {
            plugins.push(new rangePlugin({
                'input': $this.data('range')
            }));
        }
        if ($this.data('confirmDate')) {
            plugins.push(new confirmDatePlugin);
        }
        config.plugins = plugins;
    });
})(jQuery);
