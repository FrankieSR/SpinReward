define([
    'Magento_Ui/js/form/element/abstract',
    'ko',
    'jquery'
], function (Abstract, ko, $) {
    'use strict';

    return Abstract.extend({
        defaults: {
            template: 'Doroshko_WishReward/wheel-config-visual',
            value: '[]',
            defaultSector: {
                label: 'New Sector',
                rule_id: null,
                probability: 10,
                text_color: '#000000',
                background_color: '#FFFFFF',
                border_color: '#CCCCCC'
            }
        },

        sectors: ko.observableArray([]),
        ruleOptions: ko.observableArray([]),

        initialize: function () {
            this._super();
            this.sectors = ko.observableArray([]);
            this.loadFromValue();
            // this.loadRuleOptions();

            this.sectors.subscribe(this.saveToValue.bind(this));
            this.value.subscribe(this.loadFromValue.bind(this));

            return this;
        },

        loadFromValue: function () {
            let raw = this.value() || '[]';
            try {
                let arr = JSON.parse(raw);
                this.sectors(arr.map(sector => ko.observable(sector)));
            } catch (e) {
                console.error('Invalid JSON in wheel_config:', e);
                this.sectors([]);
            }
        },

        saveToValue: function () {
            let sectors = this.sectors().map(sector => ko.toJS(sector));
            this.value(JSON.stringify(sectors));
        },

        loadRuleOptions: function () {
            $.ajax({
                url: '/admin/wishreward/salesrule/getrules',
                method: 'GET',
                dataType: 'json',
                headers: {
                    'X-Csrf-Token': window.FORM_KEY
                },
                showLoader: false,
                success: function (response) {
                    // this.ruleOptions(response.items || []);
                }.bind(this),
                error: function (xhr, status, error) {
                    console.error('Failed to load sales rules:', error);
                    // this.ruleOptions([]);
                }
            });
        },

        addSector: function () {
            this.sectors.push(ko.observable(Object.assign({}, this.defaultSector)));
        },

        removeSector: function (sector) {
            this.sectors.remove(sector);
        }
    });
});
