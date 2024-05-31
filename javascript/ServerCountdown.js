/* global jquery, window */

/**
 * Countdown
 * @deprecated Use countdown-element instead
 */
(function ($, window) {
  // https://github.com/uxitten/polyfill/blob/master/string.polyfill.js
  // https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/String/padStart
  if (!String.prototype.padStart) {
    String.prototype.padStart = function padStart(targetLength, padString) {
      targetLength = targetLength >> 0; //truncate if number or convert non-number to 0;
      padString = String(typeof padString !== "undefined" ? padString : " ");
      if (this.length > targetLength) {
        return String(this);
      } else {
        targetLength = targetLength - this.length;
        if (targetLength > padString.length) {
          padString += padString.repeat(targetLength / padString.length); //append to original to ensure we are longer than needed
        }
        return padString.slice(0, targetLength) + String(this);
      }
    };
  }

  function asDate(str) {
    if (!str) {
      return new Date();
    }
    if (str instanceof Date) {
      return str;
    }
    return new Date(str);
  }

  $.fn.extend({
    ServerCountdown: function (options) {
      this.defaultOptions = {
        onInit: null,
        onTick: null,
        onComplete: null,
        reloadOnComplete: false,
        selectors: null,
        labels: {
          days: "d",
          hours: "h",
          minutes: "m",
          seconds: "s",
        },
        // in ms
        interval: 1000,
      };

      var settings = $.extend({}, this.defaultOptions, options);

      return this.each(function () {
        var $this = $(this);

        var start = $this.data("start");
        var end = $this.data("end");
        var url = $this.data("url");

        // Keep default state
        if (!start || !end) {
          return;
        }

        var startDate = asDate(start);
        var endDate = asDate(end);
        var nowDate = asDate();

        // diff in milliseconds
        var data = {};
        data.diff = endDate.getTime() - startDate.getTime();

        // Compute our initial difference based on current time (useful for interval)
        data.initDiff = nowDate.getTime() - startDate.getTime();

        if (settings.onInit) {
          settings.onInit.call();
        }

        var compute = function () {
          if (data.diff <= 0) {
            clearInterval(interval);
            if (settings.onComplete) {
              settings.onComplete.call();
            }
            if (settings.selectors && settings.selectors.seconds) {
              $(settings.selectors.seconds).text("00");
            } else {
              $this.text("00" + settings.labels.seconds);
            }
            if (settings.reloadOnComplete) {
              window.location.reload();
            } else if (url) {
              window.location.replace(url);
            }
            return;
          }

          data.days = Math.floor(data.diff / (1000 * 60 * 60 * 24));
          data.hours = Math.floor((data.diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
          data.minutes = Math.floor((data.diff % (1000 * 60 * 60)) / (1000 * 60));
          data.seconds = Math.floor((data.diff % (1000 * 60)) / 1000);

          if (settings.selectors) {
            if (settings.selectors.days) {
              $(settings.selectors.days).text(data.days);
            }
            if (settings.selectors.hours) {
              $(settings.selectors.hours).text(data.hours.toString().padStart(2, "0"));
            }
            if (settings.selectors.minutes) {
              $(settings.selectors.minutes).text(data.minutes.toString().padStart(2, "0"));
            }
            if (settings.selectors.seconds) {
              $(settings.selectors.seconds).text(data.seconds.toString().padStart(2, "0"));
            }
          } else {
            var parts = [];
            if (data.days) {
              parts.push(data.days + settings.labels.days);
            }
            parts.push(data.hours.toString().padStart(2, "0") + settings.labels.hours);
            parts.push(data.minutes.toString().padStart(2, "0") + settings.labels.minutes);
            parts.push(data.seconds.toString().padStart(2, "0") + settings.labels.seconds);

            data.msg = parts.join(" ");
            $this.text(data.msg);
          }

          if (settings.onTick) {
            settings.onTick.call($this, data);
          }

          nowDate = asDate();
          data.diff = endDate.getTime() - nowDate.getTime() + data.initDiff;
        };
        compute();
        var interval = setInterval(compute, settings.interval);
      });
    },
  });
})(jQuery, window);
