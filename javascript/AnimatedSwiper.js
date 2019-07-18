;
(function($, window, document) {

    "use strict";

    // Create the defaults once
    var pluginName = "AnimatedSwiper",
        defaults = {
            propertyName: "value"
        };

    // The actual plugin constructor
    function Plugin(element, options) {
        this.element = element;

        this.settings = $.extend({}, defaults, options);
        this._defaults = defaults;
        this._name = pluginName;
        this.init();
    }

    // Define our plugin behaviour
    $.extend(Plugin.prototype, {
        init: function() {
            var self = this;
            var $el = $(this.element);

            var container = $el.attr('id');
            var pagination = $el.find('[data-swiper="pagination"]').attr('id');
            var prev = $el.find('[data-swiper="prev"]').attr('id');
            var next = $el.find('[data-swiper="next"]').attr('id');
            var items = $el.data('items');
            var autoplay = $el.data('autoplay');
            var iSlide = $el.data('initial');
            var loop = $el.data('loop');
            var center = $el.data('center');
            var effect = $el.data('effect');
            var direction = $el.data('direction');

            // Configuration
            var conf = {};

            if (items) {
                conf.slidesPerView = items
            };
            if (autoplay) {
                conf.autoplay = autoplay
            };
            if (iSlide) {
                conf.initialSlide = iSlide
            };
            if (center) {
                conf.centeredSlides = center
            };
            if (loop) {
                conf.loop = loop
            };
            if (effect) {
                conf.effect = effect
            };
            if (direction) {
                conf.direction = direction
            };
            if (prev) {
                conf.prevButton = '#' + prev
            };
            if (next) {
                conf.nextButton = '#' + next
            };
            if (pagination) {
                conf.pagination = '#' + pagination,
                    conf.paginationClickable = true
            };

            // Animate Function
            function animated_swiper(selector, init) {
                var animated = function() {
                    $(selector + ' [data-animate]').each(function() {
                        var anim = $(this).data('animate');
                        var delay = $(this).data('delay');
                        var duration = $(this).data('duration');

                        $(this).removeClass('anim' + anim)
                            .addClass(anim + ' animated')
                            .css({
                                webkitAnimationDelay: delay,
                                animationDelay: delay,
                                webkitAnimationDuration: duration,
                                animationDuration: duration
                            })
                            .one('webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend', function() {
                                $(this).removeClass(anim + ' animated');
                            });
                    });
                };
                animated();
                // Make animated when slide change
                init.on('slideChangeTransitionStart', function() {
                    $(initID + ' [data-animate]').removeClass('animated');
                });
                init.on('slideChange', animated);
            };

            // Initialization
            if (container) {
                var initID = '#' + container;

                var init = new Swiper(initID, conf);
                animated_swiper(initID, init);
            };

        },
        log: function(text) {
            console.log(text);
        }
    });

    // Register the plugin in $ namespace
    $.fn[pluginName] = function(options) {
        return this.each(function() {
            if (!$.data(this, "plugin_" + pluginName)) {
                $.data(this, "plugin_" +
                    pluginName, new Plugin(this, options));
            }
        });
    };

})(jQuery, window, document);
