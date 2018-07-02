/* global confirmDatePlugin */
(function ($) {
    $.fn.ModularBehaviour.beforeHooks.flatpickr = function (config) {
        var $this = $(this);
        var plugins = [];
        if ($this.data('range')) {
            // Range plugin is not quite there yet
            // @link https://github.com/flatpickr/flatpickr/issues/1208
            /*plugins.push(new rangePlugin({
                'input': $this.data('range')
            }));*/
            var $other = $($this.data('range'));
            $other.data('rangeStart', '#' + $this.attr('id'));
        }
        if ($this.data('confirmDate')) {
            plugins.push(new confirmDatePlugin);
        }
        config.plugins = plugins;
    };
    $.fn.ModularBehaviour.afterHooks.flatpickr = function () {
        var $this = $(this);
        if ($this.data('rangeStart')) {
            var $other = $($this.data('rangeStart'));
            $this.on('change', function () {
                var fp = document.querySelector("#" + $other.attr('id'))._flatpickr;
                fp.set('maxDate', $this.val());
            }).trigger('change');
            $other.on('change', function () {
                var fp = document.querySelector("#" + $this.attr('id'))._flatpickr;
                fp.set('minDate', $other.val());
            }).trigger('change');
        }
    };
})(jQuery);
