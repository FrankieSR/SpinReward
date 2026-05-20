define(['Magento_Ui/js/form/components/fieldset'], (Fieldset) => {
    'use strict';

    const FIELD_GROUPS = {
        header: ['popup_company_logo', 'popup_company_text'],
        body: ['popup_title', 'popup_description'],
        footer: ['popup_button_text'],
        secondary: ['popup_decline_text', 'popup_close_text'],
        advanced: ['popup_terms_text', 'is_wish_area_enabled'],
    };

    return Fieldset.extend({
        defaults: {
            template: 'Doroshko_SpinReward/popup-settings-visual',
        },

        getFieldElements() {
            return typeof this.elems === 'function' ? this.elems() : [];
        },

        getElementName(element) {
            if (!element) {
                return '';
            }

            if (typeof element.index === 'string' && element.index) {
                return element.index;
            }

            if (typeof element.getName === 'function') {
                const name = element.getName();

                return name ? String(name).split('.').pop() : '';
            }

            if (typeof element.name === 'string' && element.name) {
                return String(element.name).split('.').pop();
            }

            return '';
        },

        getSlotElements(slot) {
            const allowed = FIELD_GROUPS[slot] || [];
            const elements = this.getFieldElements();

            return allowed.map((name) => elements.find((element) => this.getElementName(element) === name)).filter(Boolean);
        },

        getSlotCount(slot) {
            return this.getSlotElements(slot).length;
        },

        getPopupCopy() {
            return 'Title, description and logo match the front popup layout.';
        },

        getAdvancedCopy() {
            return 'Decline, close, terms and wish area sit lower because they are secondary or optional.';
        },
    });
});
