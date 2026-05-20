define(['jquery'], function ($) {
    'use strict';

    return {
        track: function (eventType, payload) {
            payload = payload || {};
            payload.event_type = eventType;

            if (!payload.form_key && window.FORM_KEY) {
                payload.form_key = window.FORM_KEY;
            }

            if (!payload.eventUrl) {
                return;
            }

            if (navigator.sendBeacon) {
                try {
                    var data = new FormData();
                    Object.keys(payload).forEach(function (key) {
                        if (payload[key] !== undefined && payload[key] !== null) {
                            data.append(key, payload[key]);
                        }
                    });

                    if (navigator.sendBeacon(payload.eventUrl, data)) {
                        return;
                    }
                } catch (e) {
                    // fallback below
                }
            }

            $.ajax({
                url: payload.eventUrl,
                method: 'POST',
                dataType: 'json',
                data: payload,
                global: false
            });
        }
    };
});
