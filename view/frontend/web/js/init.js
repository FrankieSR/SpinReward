/* global FORM_KEY */

define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'underscore',
    'domReady!',
    'mage/cookies',
    'Doroshko_SpinReward/js/analytics/tracker'
], function ($, modal, _, domReady, cookies, tracker) {
    'use strict';

    return function (config, element) {
        const wrapper      = element;
        const ctaButton    = document.getElementById('wishreward-cta-button');
        const ctaWrapper   = document.getElementById('wishreward-cta-wrapper');
        const reopenBtn    = document.getElementById('wishreward-reopen-button');
        const reopenHolder = document.getElementById('wishreward-closed-wrapper');
        const bannerClose  = document.getElementById('wishreward-banner-close');
        const bannerContent = document.getElementById('wishreward-banner-content');
        const bannerMessage = document.getElementById('wishreward-banner-message');
        const bannerCouponRow = document.getElementById('wishreward-banner-coupon-row');
        const bannerCoupon = document.getElementById('wishreward-banner-coupon');

        const { ajaxUrl, eventUrl, wheelId, completionKey = null, resultKey = null, triggerConfig = {}, bannerState = {} } = config;

        let popupClosed = false;
        let popupShown  = false;

        let $popupInstance = null;

        function getBannerStateKey() {
            if (!resultKey) {
                return null;
            }

            return `${resultKey}:banner_state`;
        }

        function readBannerState() {
            const key = getBannerStateKey();
            if (!key || !window.sessionStorage) {
                return {};
            }

            try {
                const raw = window.sessionStorage.getItem(key);
                return raw ? JSON.parse(raw) : {};
            } catch (e) {
                return {};
            }
        }

        function writeBannerState(mode) {
            const key = getBannerStateKey();
            if (!key || !window.sessionStorage) {
                return;
            }

            window.sessionStorage.setItem(key, JSON.stringify({
                mode: mode,
                updatedAt: Date.now()
            }));
        }

        function isSessionDismissed() {
            return readBannerState().mode === 'dismissed';
        }

        function isSessionClosed() {
            return readBannerState().mode === 'closed';
        }

        function dismissForSession() {
            writeBannerState('dismissed');
        }

        function markClosedForSession() {
            writeBannerState('closed');
        }

        function readCompletionState() {
            if (!completionKey) {
                return false;
            }

            if ($.cookie(completionKey) === '1') {
                return true;
            }

            try {
                if (window.localStorage) {
                    const raw = window.localStorage.getItem(completionKey);
                    if (raw) {
                        const parsed = JSON.parse(raw);
                        const expiresAt = Number(parsed && parsed.expiresAt) || 0;

                        if (expiresAt && Date.now() <= expiresAt) {
                            return true;
                        }

                        window.localStorage.removeItem(completionKey);
                    }
                }
            } catch (e) {
                // Fallback to cookie below.
            }

            return false;
        }

        function hideEntryPoints() {
            if (ctaWrapper) {
                ctaWrapper.style.display = 'none';
            }

            if (reopenHolder && !triggerConfig.isSpinCompleted) {
                reopenHolder.style.display = 'none';
            }
        }

        function showCompletedBanner(result = {}) {
            if (reopenHolder) {
                reopenHolder.style.display = 'flex';
            }

            if (reopenBtn) {
                reopenBtn.style.display = '';
            }

            if (bannerContent) {
                bannerContent.style.display = 'none';
            }

            if (ctaWrapper) {
                ctaWrapper.style.display = 'none';
            }
        }

        function showClosedBanner() {
            if (reopenHolder) {
                reopenHolder.style.display = '';
            }

            if (reopenBtn) {
                reopenBtn.style.display = '';
            }

            if (bannerContent) {
                bannerContent.style.display = 'none';
            }
        }

        function escapeHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function buildCompletedPopupHtml(result = {}) {
            const message = escapeHtml(result.message || '');
            const couponCode = escapeHtml(result.coupon_code || '');

            return `
                <div class="wishreward-popup-wrapper">
                    <div class="wishreward-popup wishreward-popup--completed">
                        <div class="wishreward__result" style="display:block;">
                            ${message ? `<h2 class="wishreward__result-title">${message}</h2>` : ''}
                            ${couponCode ? `
                                <p class="wishreward__result-text wishreward__result-text--success">
                                    ${escapeHtml('Your coupon code is:')}
                                    <strong class="wishreward__coupon-code">${couponCode}</strong>
                                </p>
                            ` : ''}
                        </div>
                    </div>
                </div>`;
        }

        function openCompletedModal(result = {}) {
            const html = buildCompletedPopupHtml(result);

            if ($popupInstance) {
                try {
                    $popupInstance.modal('closeModal');
                } catch (e) {
                    // ignore
                }
                $popupInstance.remove();
                $popupInstance = null;
            }

            $popupInstance = $('<div class="wishreward-popup-wrapper">')
                .html(html)
                .modal({
                    type:             'popup',
                    modalClass:       'wishreward-popup wishreward-popup--completed',
                    responsive:       true,
                    outerClickHandler: false,
                    innerScroll:      false,
                    buttons:          false,
                    closed:           handleClose,
                    clickableOverlay: false
                });

            $(wrapper).trigger('contentUpdated');
            $popupInstance.modal('openModal');
            popupShown = true;
        }

        if (isSessionDismissed()) {
            hideEntryPoints();
            if (reopenHolder) {
                reopenHolder.style.display = 'none';
            }
            return;
        }

        if (triggerConfig.isSpinCompleted || readCompletionState()) {
            showCompletedBanner(bannerState.result || {});
            return;
        }

        if (isSessionClosed()) {
            showClosedBanner();
            if (ctaWrapper) {
                ctaWrapper.style.display = 'none';
            }
        }

        if (eventUrl && ctaWrapper) {
            tracker.track('popup_impression', {
                eventUrl: eventUrl,
                wheel_id: wheelId,
                form_key: $.cookie('form_key')
            });
        }

        function handleClose() {
            popupClosed = true;

            if (!readCompletionState() && !isSessionDismissed()) {
                markClosedForSession();
                showClosedBanner();
            }
        }

        function openModal() {
            if (readCompletionState() || isSessionDismissed()) {
                hideEntryPoints();
                return;
            }

            if (triggerConfig.isSpinCompleted) {
                openCompletedModal((bannerState && bannerState.result) || {});
                return;
            }

            if (popupClosed || popupShown) {
                return;
            }

            if ($popupInstance) {
                $popupInstance.modal('openModal');
                popupShown = true;
                if (eventUrl) {
                    tracker.track('popup_open', {
                        eventUrl: eventUrl,
                        wheel_id: wheelId,
                        form_key: $.cookie('form_key')
                    });
                }
                return;
            }

            $.ajax({
                url:        ajaxUrl,
                method:     'POST',
                dataType:   'json',
                showLoader: true,
                data: { 
                    wheel_id: wheelId,
                    form_key: $.cookie("form_key") 
                },

                success(response) {
                if (response.already_completed) {
                    showCompletedBanner(response.result || response || {});
                    openCompletedModal(response.result || bannerState.result || {});
                    return;
                }

                if (!response.success || !response.html) {
                    console.error('Wishreward Popup Error:', response);
                    return;
                }

                    $popupInstance = $('<div class="wishreward-popup-wrapper">')
                        .html(response.html)
                        .modal({
                            type:             'popup',
                            modalClass:       'wishreward-popup',
                            responsive:       true,
                            outerClickHandler: false,
                            innerScroll:      false,
                            buttons:          false,
                            closed:           handleClose,
                            clickableOverlay: false
                        });

                    $(wrapper).trigger('contentUpdated');

                    $popupInstance.modal('openModal');
                    popupShown = true;

                    if (eventUrl) {
                        tracker.track('popup_open', {
                            eventUrl: eventUrl,
                            wheel_id: wheelId,
                            form_key: $.cookie('form_key')
                        });
                    }

                    if (ctaWrapper) {
                        ctaWrapper.style.display = 'none';
                    }

                    if (bannerContent && response.coupon_code) {
                        bannerMessage.textContent = response.message || '';
                        bannerCoupon.textContent = response.coupon_code || '';
                        bannerCouponRow.style.display = '';
                    }

                    $popupInstance.on('click', '#wishreward-opt-out-link', () => {
                        $popupInstance.modal('closeModal');
                    });
                },

                error(xhr) {
                    console.error('Wishreward AJAX Error:', {
                        status: xhr.status,
                        responseText: xhr.responseText
                    });
                }
            });
        }

        if (reopenBtn) {
            reopenBtn.addEventListener('click', e => {
                e.preventDefault();
                if (isSessionDismissed()) {
                    return;
                }

                if (triggerConfig.isSpinCompleted || readCompletionState()) {
                    openCompletedModal((bannerState && bannerState.result) || {});
                    return;
                }

                writeBannerState(null);
                popupClosed = false;
                popupShown  = false;
                showClosedBanner();
                openModal();
            });
        }

        if (bannerClose) {
            bannerClose.addEventListener('click', e => {
                e.preventDefault();
                dismissForSession();
                if (reopenHolder) {
                    reopenHolder.style.display = 'none';
                }
                hideEntryPoints();
            });
        }

        if (triggerConfig.isCtaEnabled && ctaButton) {
            ctaButton.addEventListener('click', e => {
                e.preventDefault();
                if (readCompletionState()) {
                    hideEntryPoints();
                    return;
                }
                if (eventUrl) {
                    tracker.track('cta_click', {
                        eventUrl: eventUrl,
                        wheel_id: wheelId,
                        form_key: $.cookie('form_key')
                    });
                }
                if (ctaWrapper) {
                    ctaWrapper.style.display = 'none';
                }
                openModal();
            });
        }

        if (triggerConfig.isScrollEnabled) {
            const onScroll = _.throttle(() => {
                if (popupClosed || isSessionDismissed()) {
                    return;
                }

                const percent = (window.scrollY /
                    (document.documentElement.scrollHeight - window.innerHeight)) * 100;
                if (percent >= triggerConfig.scrollPercentage) {
                    openModal();
                    window.removeEventListener('scroll', onScroll);
                }
            }, 200);
            window.addEventListener('scroll', onScroll);
        }

        if (triggerConfig.isTimeoutEnabled === true && Number(triggerConfig.timeoutDuration) > 0) {
            setTimeout(() => {
                if (popupClosed || isSessionDismissed()) {
                    return;
                }
                openModal();
            }, Number(triggerConfig.timeoutDuration));
        }

        if (triggerConfig.isExitEnabled) {
            const onMouseLeave = e => {
                if (popupClosed || isSessionDismissed()) {
                    return;
                }

                if (e.clientY <= 0) {
                    openModal();
                    document.removeEventListener('mouseleave', onMouseLeave);
                }
            };
            document.addEventListener('mouseleave', onMouseLeave);
        }

        window.addEventListener('wishreward:spin-completed', event => {
            if (!event.detail || String(event.detail.wheelId) !== String(wheelId) || isSessionDismissed()) {
                return;
            }

            showCompletedBanner(event.detail.response || {});
            if (event.detail.response) {
                bannerState.result = event.detail.response;
            }
        });
    };
});
