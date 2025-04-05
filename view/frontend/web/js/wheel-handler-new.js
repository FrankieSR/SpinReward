define(['jquery', 'mage/translate', 'Doroshko_WishReward/js/lotteryWheelWidget', 'mage/validation'], ($, $t) => {
  'use strict';

  return (config, element) => {
    const DEFAULTS = Object.freeze({
      hideErrorDuration: 4000,
      rotationDuration: config.rotationDuration || 6000,
      wheelRadius: config.wheelRadius || 140,
    });

    const SELECTORS = Object.freeze({
      form: '#wishreward-form',
      wheelBox: '#wishreward-wheel',
      wishTextarea: 'textarea[name="wish"]',
      content: '#wishreward-content',
      message: '#wishreward-message',
    });

    const ERROR_CLASS = 'mage-error';

    const init = () => {
      console.log('Config:', config);
      displayWheel(config.wheelItems);
      $(document).on('submit', SELECTORS.form, handleFormSubmit);
    };

    const handleFormSubmit = (event) => {
      event.preventDefault();
      const $form = $(event.target);

      if (!$form.valid()) return;

      console.log('Form data:', $form.serialize());
      $.post(config.ajaxUrl, $form.serialize()).done(handleSpinSuccess).fail(handleError);
    };

    const handleSpinSuccess = (response) => {
      console.log('Spin success response:', response);
      if (!response.success) {
        showError(response.message || $t('An error occurred.'));
        return;
      }

      clearErrors();
      $(SELECTORS.wheelBox).lotteryWheel('spinToItem', response.sector_id, { coupon: response.coupon_code }, () => {
        $(SELECTORS.content).hide();
        $(SELECTORS.message).show().find('.success-message').text(response.message);
      });
    };

    const displayWheel = (items) => {
      if (!items?.length) {
        $(SELECTORS.wheelBox).html(`<p>${$t('Wheel configuration is missing.')}</p>`);
        return;
      }

      const container = $(SELECTORS.wheelBox);
      if (config.wheelPosition === 'left') container.css('margin-right', 'auto');
      else if (config.wheelPosition === 'right') container.css('margin-left', 'auto');
      else container.css({ 'margin-left': 'auto', 'margin-right': 'auto' });

      container.lotteryWheel({
        items,
        rotationDuration: DEFAULTS.rotationDuration,
        wheelRadius: DEFAULTS.wheelRadius,
        centerColor: '#FFFFFF',
        pointerColor: '#018749',
        outerRingColor: '#FFFFFF',
        outerRingWidth: 10,
      });
    };

    const showError = (message) => {
      $(SELECTORS.wheelBox).after(`<div class="${ERROR_CLASS} message">${message}</div>`);
      setTimeout(clearErrors, DEFAULTS.hideErrorDuration);
    };

    const clearErrors = () => {
      $(SELECTORS.form).find(`.${ERROR_CLASS}`).removeClass(ERROR_CLASS).filter('.message').remove();
    };

    const handleError = (xhr) => {
      console.error('Error processing spin:', xhr);
      showError($t('An error occurred while spinning the wheel.'));
    };

    init();
  };
});
