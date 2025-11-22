/* global FORM_KEY */

define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'underscore',
    'domReady!',
    'mage/cookies'
], function ($, modal, _) {
    'use strict';

    return function (config, element) {
        const wrapper      = element;
        const ctaButton    = document.getElementById('wishreward-cta-button');
        const ctaWrapper   = document.getElementById('wishreward-cta-wrapper');
        const reopenBtn    = document.getElementById('wishreward-reopen-button');
        const reopenHolder = document.getElementById('wishreward-closed-wrapper');

        const { ajaxUrl, wheelId, triggerConfig = {} } = config;

        console.log(config, 'configconfig');

        let popupClosed = false;
        let popupShown  = false;

        let $popupInstance = null;

        function handleClose() {
            popupClosed = true;

            if (reopenHolder) {
                reopenHolder.style.display = '';
            }
        }

        function openModal() {
            if (popupClosed || popupShown) {
                return;
            }

            if ($popupInstance) {
                $popupInstance.modal('openModal');
                popupShown = true;
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

                    if (ctaWrapper) {
                        ctaWrapper.style.display = 'none';
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
                popupClosed = false;
                popupShown  = false;
                if (reopenHolder) {
                    reopenHolder.style.display = 'none';
                }
                openModal();
            });
        }

        if (triggerConfig.isCtaEnabled && ctaButton) {
            ctaButton.addEventListener('click', e => {
                e.preventDefault();
                if (ctaWrapper) {
                    ctaWrapper.style.display = 'none';
                }
                openModal();
            });
        }

        if (triggerConfig.isScrollEnabled) {
            const onScroll = _.throttle(() => {
                const percent = (window.scrollY /
                    (document.documentElement.scrollHeight - window.innerHeight)) * 100;
                if (percent >= triggerConfig.scrollPercentage) {
                    openModal();
                    window.removeEventListener('scroll', onScroll);
                }
            }, 200);
            window.addEventListener('scroll', onScroll);
        }

        if (triggerConfig.isTimeoutEnabled) {
            setTimeout(openModal, triggerConfig.timeoutDuration);
        }

        if (triggerConfig.isExitEnabled) {
            const onMouseLeave = e => {
                if (e.clientY <= 0) {
                    openModal();
                    document.removeEventListener('mouseleave', onMouseLeave);
                }
            };
            document.addEventListener('mouseleave', onMouseLeave);
        }
    };
});
