define(['jquery', 'mage/translate', 'Doroshko_WishReward/js/lotteryWheelWidget', 'mage/validation'], function ($, $t) {
    'use strict';

    return function (config, element) {
        const defaults = {
            hideErrorDuration: 4000,
            rotationDuration: config.rotationDuration || 6000,
        };

        const sel = {
            form: '#wishreward-form',
            wheelBox: '#wishreward-wheel',
            wheelSection: '.wishreward-wheel-section',
            infoSection: '.wishreward-info-main',
            messagePanel: '#wishreward-message',
            couponContainer: '#wish-coupon-container',
            noCouponContainer: '#wish-nocoupon-container',
            couponCode: '#wish-coupon-code',
            consentInput: '#consent-input',
            errorContainer: '.error-container',
            errorMessage: '.error-message'
        };

        let errorTimer;

        function init() {
            $(sel.form).validate({
                errorClass: 'mage-error',
                errorElement: 'div',
                errorPlacement: function (error, element) {
                    error.insertAfter(element);
                },
                messages: {
                    consent_given: $t('You must agree to the Privacy Policy')
                }
            });

            renderWheel();
            $(sel.form).on('submit', handleSubmit);
        }

        function renderWheel() {
            const $box = $(sel.wheelBox);
            const items = config.wheelItems;
            if (!Array.isArray(items) || items.length === 0) {
                $box.html(`<p>${$t('Wheel configuration is missing.')}</p>`);
                return;
            }

            $box.lotteryWheel({
                items,
                rotationDuration: defaults.rotationDuration,
                centerColor: '#FFFFFF',
                pointerColor: '#018749',
                outerRingColor: '#FFFFFF',
                outerRingWidth: 10,
            });
        }

        function handleSubmit(e) {
            e.preventDefault();
            const $form = $(this);

            if (!$form.valid()) {
                return;
            }

            if (!$(sel.consentInput).is(':checked')) {
                showError($t('You must agree to the Privacy Policy'));
                return;
            }

            $.post(config.ajaxUrl, $form.serialize())
                .done(onSpinSuccess)
                .fail(onSpinError);
        }

        function onSpinSuccess(response) {
            if (!response.success) {
                showError($t(response.reason || 'An error occurred.'));
                return;
            }

            clearError();

            // Spin animation
            $(sel.wheelBox).lotteryWheel('spinToItem', response.sector_id, {
                coupon: response.coupon_code
            }, () => {
                hideInitialUI();
                displayResult(response);
            });
        }

        function onSpinError(xhr) {
            showError($t('An error occurred while spinning the wheel.'));
        }

        function hideInitialUI() {
            $(sel.wheelSection).hide();
            $(sel.infoSection).hide();
        }

        function displayResult({ message, coupon_code }) {
            const $panel = $(sel.messagePanel);
            const $couponDiv = $(sel.couponContainer);
            const $noCouponDiv = $(sel.noCouponContainer);
            const $codeSpan = $(sel.couponCode);

            // Set headline message
            $panel.find('.success-message').text(message);

            if (coupon_code) {
                $codeSpan.text(coupon_code);
                $couponDiv.show().attr('aria-hidden', 'false');
                $noCouponDiv.hide().attr('aria-hidden', 'true');
            } else {
                $noCouponDiv.show().attr('aria-hidden', 'false');
                $couponDiv.hide().attr('aria-hidden', 'true');
            }

            $panel.show();
        }

        function showError(msg) {
            clearError();

            const $errorContainer = $(sel.errorContainer);
            const $errorMessage = $(sel.errorMessage);

            $errorMessage.text(msg).show();
            $errorContainer.show();

            errorTimer = setTimeout(clearError, defaults.hideErrorDuration);
        }

        function clearError() {
            clearTimeout(errorTimer);
            $(sel.errorContainer).hide();
            $(sel.errorMessage).text('').hide();
        }

        init();
    };
});
