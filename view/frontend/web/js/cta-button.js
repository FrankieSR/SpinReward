define(['jquery', 'Magento_Ui/js/modal/modal'], function ($, modal) {
    'use strict';

    return function (config, element) {
        var $wrapper = $(element),
            ajaxUrl = config.ajaxUrl,
            wheelId = config.wheelId;

        if (!ajaxUrl || !wheelId) {
            console.error('CTA Popup Error: Missing ajaxUrl or wheelId in config.', {
                ajaxUrl: ajaxUrl,
                wheelId: wheelId,
            });

            return;
        }

        $wrapper.find('.wishreward-cta-button').on('click', function () {
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                dataType: 'json',
                showLoader: true,
                data: {
                    wheel_id: wheelId,
                },
                success: function (response) {
                    if (response.success && response.html) {
                        var popup = $('<div>', {
                                class: 'wishreward-popup-wrapper',
                            })
                            .html(response.html)
                            .modal({
                                type: 'popup',
                                title: null,
                                modalClass: 'wishreward-popup',
                                responsive: true,
                                outerClickHandler: false,
                                innerScroll: true,
                                responsiveClass: false,
                                buttons: false
                            });
                        
                        $wrapper.trigger('contentUpdated');
                        popup.modal('openModal');
                        
                    } else {
                        console.error('CTA Popup Error: Invalid response.', response);
                    }
                },
                error: function (xhr, status, error) {
                    var responseText = xhr.responseText || 'No response text';
                    console.error('CTA Popup AJAX Error:', {
                        status: status,
                        error: error,
                        responseText: responseText,
                    });
                },
            });
        });
    };
});
