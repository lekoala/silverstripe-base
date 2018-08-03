/* global jquery, moment */

/**
 * A server side countdown
 */
;
(function ($, moment) {
    $.fn.extend({
        ServerCountdown: function (options) {
            this.defaultOptions = {
                onInit: null,
                onTick: null,
                onComplete: null,
                labels: {
                    days: 'd',
                    hours: 'h',
                    minutes: 'm',
                    seconds: 's'
                },
                // in ms
                interval: 1000,
                serverSyncUrl: '/__time',
                // in seconds
                serverPoll: 30
            };

            var settings = $.extend({}, this.defaultOptions, options);

            return this.each(function () {
                var $this = $(this);

                var start = $this.data('start');
                var end = $this.data('end');

                if (!start || !end) {
                    console.log("Must define data-start and data-end");
                    return;
                }

                var startDate = moment(start);
                var endDate = moment(end);

                // diff in milliseconds
                var data = {};
                data.diff = endDate.diff(startDate);
                var poll = settings.serverPoll;

                if (settings.onInit) {
                    settings.onInit.call();
                }

                var interval = setInterval(function () {
                    if (data.diff <= 0) {
                        clearInterval(interval);
                        if (settings.onComplete) {
                            settings.onComplete.call();
                        }
                        $this.text("0 " + settings.labels.seconds);
                        return;
                    }

                    data.days = Math.floor(data.diff / (1000 * 60 * 60 * 24));
                    data.hours = Math.floor((data.diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    data.minutes = Math.floor((data.diff % (1000 * 60 * 60)) / (1000 * 60));
                    data.seconds = Math.floor((data.diff % (1000 * 60)) / 1000);

                    var parts = [];
                    if (data.days) {
                        parts.push(data.days + settings.labels.days);
                    }
                    parts.push(data.hours.toString().padStart(2, "0") + settings.labels.hours);
                    parts.push(data.minutes.toString().padStart(2, "0") + settings.labels.minutes);
                    parts.push(data.seconds.toString().padStart(2, "0") + settings.labels.seconds);

                    data.msg = parts.join(' ');
                    $this.text(data.msg);

                    if (settings.onTick) {
                        settings.onTick.call($this, data);
                    }

                    data.diff -= settings.interval;

                    // Check if needs polling
                    poll--;
                    if (poll <= 0) {
                        poll = settings.serverPoll;

                        $.getJSON(settings.serverSyncUrl, function (result) {
                            startDate = moment(result.date);
                            var newDiff = endDate.diff(startDate);
                            // It can only go down
                            if (newDiff < data.diff) {
                                data.diff = newDiff;
                            }
                        });
                    }
                }, settings.interval);
            });
        }
    });
})(jQuery, moment);
