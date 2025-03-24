define([
    'Magento_Ui/js/form/element/textarea'
], function (Textarea) {
    'use strict';

    return Textarea.extend({
        defaults: {
            visible: false
        },

        initialize: function () {
            this._super();
            // Доп. логика
            return this;
        },

        toggleVisibility: function () {
            this.visible = !this.visible;
        }
    });
});
