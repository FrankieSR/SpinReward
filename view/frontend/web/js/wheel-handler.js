define([
    'jquery',
    'mage/translate',
    'Doroshko_WishReward/js/lottery-wheel',
    'mage/validation'
], function ($, $t) {
    'use strict';

    return function (config, element) {
        const DEFAULTS = {
            hideErrorDuration: 4000,
            rotationDuration: 6000,
            wheelRadius: 140
        };

        const selectors = {
            form: $(element).find('#wheel-form'),
            wheelBox: $('#lottery-wheel'),
            submitButton: $('.submit-wheel'),
            wishTextarea: $(element).find('textarea[name="wish"]')
        };

        init();

        function init() {
            displayWheel(config.wheelItems);
            selectors.form.on('submit', handleFormSubmit);
        }

        function handleFormSubmit(event) {
            event.preventDefault();
            if (!selectors.form.valid()) return;

            $.post(config.ajaxUrl, selectors.form.serialize())
                .done(handleSpinSuccess)
                .fail(handleError);
        }

        function handleSpinSuccess(response) {
            if (!response.success) {
                showError(response.message || $t('An error occurred.'));
                return;
            }

            clearErrors();
            selectors.wheelBox.lotteryWheel('spinToItem', response.sector_id, { coupon: response.coupon_code }, function(result) {
                alert(response.message);
            });
        }

        function displayWheel(items) {
            if (!items || items.length === 0) {
                selectors.wheelBox.html('<p>' + $t('Wheel configuration is missing.') + '</p>');
                return;
            }

            selectors.wheelBox.lotteryWheel({
                items: items,
                rotationDuration: DEFAULTS.rotationDuration,
                wheelRadius: DEFAULTS.wheelRadius,
                colors: ["rgba(255, 69, 0, 0.9)", "#FFD700", "#018749"],
                textColor: "#000",
                borderColor: "#1C2541",
                borderWidth: 1,
                centerColor: "#0B132B",
                pointerColor: "#018749",
                outerRingColor: "#1C2541",
                outerRingWidth: 10,
                fontSize: 18,
                fontWeight: "bold"
            });
        }

        function showError(message) {
            const errorClass = 'mage-error';
            selectors.wishTextarea.addClass(errorClass).after(`<div class="${errorClass} message">${message}</div>`);
            setTimeout(clearErrors, DEFAULTS.hideErrorDuration);
        }

        function clearErrors() {
            selectors.form.find('.mage-error').removeClass('mage-error');
            selectors.form.find('.mage-error.message').remove();
        }

        function handleError(xhr) {
            console.error('Error processing spin:', xhr);
            showError($t('An error occurred while spinning the wheel.'));
        }
    };
});