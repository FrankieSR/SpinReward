define([
    'jquery',
    'mage/translate',
    'Doroshko_WishReward/js/lotteryWheelWidget',
    'mage/validation',
    'mage/cookies',
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
            submitButton: '.wishreward__button',
            spinMoreButton: '.wishreward-spin-more',
        };

        let errorTimer;

        function setCookie(name, value, seconds) {
            const expires = new Date();
            expires.setTime(expires.getTime() + seconds * 1000);
            $.cookie(name, value, {
                expires: seconds / 86400,
                path: '/',
                sameSite: 'Lax',
            });
        }

        function getCookie(name) {
            return $.cookie(name);
        }

        function getCookieLifetime() {
            const per = parseInt(config.attemptsPeriod || 1, 10);
            const unit = config.attemptsPeriodUnit;

            if (unit === 'forever') {
                return 60 * 60 * 24 * 365 * 10;
            }

            const multipliers = {
                day: 86400,
                days: 86400,
                week: 7 * 86400,
                month: 30 * 86400,
                year: 365 * 86400,
                years: 365 * 86400,
            };

            return (multipliers[unit] || multipliers['day']) * per;
        }

        function hashEmail(email) {
            try {
                return btoa(encodeURIComponent(email)).slice(0, 20);
            } catch (e) {
                console.warn('Failed to hash email:', e);
                return '';
            }
        }

        function getSpinCookieKey(email) {
            return 'wishreward_' + hashEmail(email);
        }

        function getSpinAttempts(email) {
            const key = getSpinCookieKey(email);
            const raw = getCookie(key);
            if (!raw) return [];

            try {
                const parsed = JSON.parse(raw);
                return Array.isArray(parsed) ? parsed : [];
            } catch (e) {
                return [];
            }
        }

        function addSpinAttempt(email) {
            const key = getSpinCookieKey(email);
            const lifetime = getCookieLifetime();
            const now = Math.floor(Date.now() / 1000);
            const cutoff = now - lifetime;

            let attempts = getSpinAttempts(email);
            attempts = attempts.filter((ts) => ts > cutoff);

            attempts.push(now);
            setCookie(key, JSON.stringify(attempts), lifetime);
        }

        function canSpin(email) {
            const maxAttempts = parseInt(config.attemptsPerUser, 10) || 1;
            const lifetime = getCookieLifetime();
            const now = Math.floor(Date.now() / 1000);
            const cutoff = now - lifetime;

            const attempts = getSpinAttempts(email).filter((ts) => ts > cutoff);
            return attempts.length < maxAttempts;
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

            $('.submitButton').attr('disabled', 'disabled');

            if (!$(elements.consentInput).is(':checked'))   {
                showError($t('You must agree to the Privacy Policy'));
                return;
            }

            if (email && !canSpin(email)) {
                showError($t('You have reached the maximum number of spins allowed.'));
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
            postData.page_url = window.location.href.substring(0, 512);
            postData.referrer_url = document.referrer || null;
            postData.user_agent = navigator.userAgent.substring(0, 512);
            postData.spin_count_session = getSpinAttempts(email).length;

            console.log('spin data:', postData);

            $.post(config.ajaxUrl, postData)
                .done(function (response) {
                    if (email) {
                        addSpinAttempt(email);
                    }

                    canSpin(email) ? $(elements.spinMoreButton).show() : $(elements.spinMoreButton).hide();
                    $(elements.spinMoreButton).on('click', handleClickSpinMore);
                    
                    onSpinSuccess(response, email);
                })
                .fail(onSpinError)
                .always(() => {
                    $('.submitButton').attr('disabled', '');
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

            $(elements.wheelBox).lotteryWheel(
                'spinToItem',
                response.sector_id, {
                    coupon: response.coupon_code,
                },
                () => {
                    hideInitialUI();
                    displayResult(response);
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
