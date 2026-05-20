define([
    'jquery',
    'mage/translate',
    'Doroshko_SpinReward/js/lotteryWheelWidget',
    'mage/validation'
], function ($, $t) {
    'use strict';

    return function (config, element) {
        const defaults = {
            hideErrorDuration: 5000,
            rotationDuration: config.rotationDuration || 6000,
        };

        const elements = {
            form: '#wishreward-form',
            wheelBox: '#wishreward-wheel',
            wheelSection: '.wishreward-wheel-section',
            infoSection: '#wishreward-main',
            messagePanel: '#wishreward-result',
            couponContainer: '#wish-coupon-container',
            noCouponContainer: '#wish-nocoupon-container',
            couponCode: '#wish-coupon-code',
            consentInput: '#consent-input',
            errorContainer: '.wishreward__error',
            errorMessage: '.wishreward__message-text--error',
            submitButton: '.wishreward__button--primary',
            spinMoreButton: '.wishreward-spin-more',
        };

        let errorTimer;

        function getCompletionStorageKey() {
            return config.completionKey || null;
        }

        function getCompletionExpiryDate() {
            const now = new Date();
            const periodUnit = String(config.attemptsPeriodUnit || 'day').toLowerCase();

            switch (periodUnit) {
                case 'week': {
                    const daysUntilSunday = (7 - now.getUTCDay()) % 7;
                    return new Date(Date.UTC(
                        now.getUTCFullYear(),
                        now.getUTCMonth(),
                        now.getUTCDate() + daysUntilSunday,
                        23,
                        59,
                        59,
                        999
                    ));
                }
                case 'month':
                    return new Date(Date.UTC(
                        now.getUTCFullYear(),
                        now.getUTCMonth() + 1,
                        0,
                        23,
                        59,
                        59,
                        999
                    ));
                case 'year':
                    return new Date(Date.UTC(now.getUTCFullYear(), 11, 31, 23, 59, 59, 999));
                case 'forever':
                    return new Date(Date.UTC(now.getUTCFullYear() + 10, 0, 1, 0, 0, 0, 0));
                case 'day':
                default:
                    return new Date(Date.UTC(
                        now.getUTCFullYear(),
                        now.getUTCMonth(),
                        now.getUTCDate(),
                        23,
                        59,
                        59,
                        999
                    ));
            }
        }

        function markWheelCompleted() {
            const storageKey = getCompletionStorageKey();

            if (!storageKey) {
                return;
            }

            const expiresAt = getCompletionExpiryDate().getTime();

            try {
                if (window.localStorage) {
                    window.localStorage.setItem(storageKey, JSON.stringify({
                        completedAt: Date.now(),
                        expiresAt: expiresAt
                    }));
                }
            } catch (e) {}
        }

        function init() {
            $(elements.form).validate({
                errorClass: 'mage-error',
                errorElement: 'div',
                errorPlacement: function (error, element) {
                    error.insertAfter(element);
                },
                messages: {
                    consent_given: $t('You must agree to the Privacy Policy'),
                },
            });

            renderWheel();

            $(elements.form).on('submit', handleSubmit);
        }

        function renderWheel() {
            const $box = $(elements.wheelBox);
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

        function getDeviceType() {
            const userAgent = navigator.userAgent.toLowerCase();
            if (/mobile|android|iphone/.test(userAgent)) {
                return 'mobile';
            } else if (/tablet|ipad/.test(userAgent)) {
                return 'tablet';
            }
            return 'desktop';
        }

        function getUtmParams() {
            const params = new URLSearchParams(window.location.search);
            return {
                utm_source: params.get('utm_source') || null,
                utm_medium: params.get('utm_medium') || null,
                utm_campaign: params.get('utm_campaign') || null,
            };
        }

        function handleSubmit(e) {
            e.preventDefault();
            const $form = $(this);
            const emailField = $form.find('input[name="email"]');
            const email = emailField && $form.find('input[name="email"]').val();

            if (!$form.valid()) {
                return;
            }

            $(elements.submitButton).prop('disabled', true);

            if (!$(elements.consentInput).is(':checked'))   {
                showError($t('You must agree to the Privacy Policy'));
                return;
            }

            const formData = $form.serializeArray();
            const postData = {};

            formData.forEach(function (item) {
                postData[item.name] = item.value;
            });

            postData.device_type = getDeviceType();
            const utmParams = getUtmParams();

            postData.utm_source = utmParams.utm_source;
            postData.utm_medium = utmParams.utm_medium;
            postData.utm_campaign = utmParams.utm_campaign;

            postData.page_url = window.location.href;
            postData.referrer_url = document.referrer || null;

            $.post(config.ajaxUrl, postData)
                .done(function (response) {
                    $(elements.spinMoreButton).on('click', handleClickSpinMore);

                    onSpinSuccess(response, email);
            })
            .fail(onSpinError)
            .always(() => {
                $(elements.submitButton).prop('disabled', false);
            });
        }

        function handleClickSpinMore() {
            const $panel = $(elements.messagePanel);
            const $infoSection = $(elements.infoSection);

            $panel.hide();
            $infoSection.show();
        }

        function onSpinSuccess(response, email) {
            if (!response.success) {
                showError($t(response.reason || response.message || 'An error occurred.'));
                return;
            }

            clearError();
            markWheelCompleted();

            $(elements.wheelBox).lotteryWheel(
                'spinToItem',
                response.sector_id, {
                    coupon: response.coupon_code,
                },
                () => {
                    hideInitialUI();
                    displayResult(response);
                    window.dispatchEvent(new CustomEvent('wishreward:spin-completed', {
                        detail: {
                            wheelId: config.wheelId,
                            response: response
                        }
                    }));
                }
            );
        }

        function onSpinError(xhr) {
            showError($t('An error occurred while spinning the wheel.'));
        }

        function hideInitialUI() {
            $(elements.infoSection).hide();
        }

        function displayResult({
            message,
            coupon_code
        }) {
            const $panel = $(elements.messagePanel);
            const $couponDiv = $(elements.couponContainer);
            const $noCouponDiv = $(elements.noCouponContainer);
            const $codeSpan = $(elements.couponCode);

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

            const $errorContainer = $(elements.errorContainer);
            const $errorMessage = $(elements.errorMessage);

            $errorMessage.text(msg).show();
            $errorContainer.show();

            errorTimer = setTimeout(clearError, defaults.hideErrorDuration);
        }

        function clearError() {
            clearTimeout(errorTimer);
            $(elements.errorContainer).hide();
            $(elements.errorMessage).text('').hide();
        }

        init();
    };
});
