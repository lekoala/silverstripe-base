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
            // @link http://idangero.us/swiper/api/#pagination
            var pagination = $el.data('swiper-pagination');
            // @link http://idangero.us/swiper/api/#navigation
            var prev = $el.data('swiper-prev');
            var next = $el.data('swiper-next');
            // @link http://idangero.us/swiper/api/#autoplay
            var autoplay = $el.data('autoplay');
            // @link http://idangero.us/swiper/api/#parameters
            var items = $el.data('items'); //  Number of slides per view (default : 1)
            var iSlide = $el.data('initial');
            var loop = $el.data('loop');
            var center = $el.data('center');
            var effect = $el.data('effect'); // "slide", "fade", "cube", "coverflow" or "flip"
            var direction = $el.data('direction'); // 'horizontal' or 'vertical' (for vertical slider).

            // Configuration
            // @link https://idangero.us/swiper/api/
            var conf = {};

            if (items) {
                conf.slidesPerView = items;
            }
            if (autoplay) {
                conf.autoplay = autoplay;
            }
            if (iSlide) {
                conf.initialSlide = iSlide;
            }
            if (center) {
                conf.centeredSlides = center;
            }
            if (loop) {
                conf.loop = loop;
            }
            if (effect) {
                conf.effect = effect;
            }
            if (direction) {
                conf.direction = direction;
            }
            if (prev && next) {
                var navigationConfig = {
                    "prevEl": prev,
                    "nextEl": next
                }
                conf.navigation = navigationConfig;
            }
            if (pagination) {
                var paginationConfig = {
                    "el": pagination,
                    "clickable": true
                };
                conf.pagination = paginationConfig
            }

            // Animate Function
            var animated = function() {
                // active index is needed when using loops
                var slide = 0;
                if (loop) {
                    slide = this.slides[this.activeIndex];
                } else {
                    slide = this.slides[this.currentIndex];
                }
                // console.log("slide");
                $(slide).find('[data-animate]').each(function() {

                    var animatedEl = $(this);
                    var anim = animatedEl.data('animate');
                    var delay = animatedEl.data('delay');
                    var duration = animatedEl.data('duration');

                    if (!delay) {
                        delay = '0';
                    }
                    if (!duration) {
                        duration = '1s';
                    }

                    var cssValues = {
                        webkitAnimationDelay: delay,
                        animationDelay: delay,
                        webkitAnimationDuration: duration,
                        animationDuration: duration
                    };

                    animatedEl
                        .removeClass(anim)
                        .addClass(anim + ' animated')
                        .css(cssValues)
                        .one('webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend', function() {
                            // console.log(animatedEl);
                            animatedEl.removeClass(anim + ' animated');
                            animatedEl.addClass("animated-end");
                            // console.log('anim done');
                        });
                });
            };

            // Initialization
            var initID = '#' + container;

            // Init is false to allow animation callback
            conf.init = false;
            var swiper = new Swiper(initID, conf);

            // Animate on init or slide
            swiper.on('init', animated);
            swiper.on('slideChangeTransitionStart', animated);

            swiper.init();

            // console.log(swiper);

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
