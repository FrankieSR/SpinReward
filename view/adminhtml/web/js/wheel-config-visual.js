define(['Magento_Ui/js/form/element/abstract', 'ko', 'jquery'], (Abstract, ko, $) => {
    'use strict';

    const createSector = (data, generateUniqueId, defaultSector) => {
        data = data || {};
        return {
            id: ko.observable(data.id || generateUniqueId()),
            label: ko.observable(data.label || defaultSector.label),
            rule_id: ko.observable(data.rule_id || defaultSector.rule_id),
            result_text: ko.observable(data.result_text || defaultSector.result_text),
            probability: ko.observable(data.probability || defaultSector.probability),
            text_color: ko.observable(data.text_color || defaultSector.text_color),
            background_color: ko.observable(data.background_color || defaultSector.background_color),
            border_color: ko.observable(data.border_color || defaultSector.border_color),
        };
    };

    return Abstract.extend({
        defaults: {
            template: 'Doroshko_WishReward/wheel-config-visual',
            defaultSector: {
                id: null,
                label: 'New Sector',
                result_text: '',
                rule_id: null,
                probability: 10,
                text_color: '#000000',
                background_color: '#FFFFFF',
                border_color: '#CCCCCC',
            },
            priceRuleOptions: [],
            imports: {
                'source': '${ $.provider }:data'
            }
        },

        cartPriceRuleOptions: ko.observableArray([]),
        sectors: ko.observableArray([]),
        isPopupVisible: ko.observable(false),
        popupSector: ko.observable(null),
        isEditing: ko.observable(false),

        initialize() {
            this._super();
            this.cartPriceRuleOptions(this.priceRuleOptions || []);

            this.closePopup = this.closePopup.bind(this);
            this.savePopupSector = this.savePopupSector.bind(this);
            this.openAddSectorPopup = this.openAddSectorPopup.bind(this);
            this.editSector = this.editSector.bind(this);
            this.removeSector = this.removeSector.bind(this);

            if (!this.source) {
                console.warn('Source is not available during initialization');
            }

            this.loadFromValue();

            this.value.subscribe((newValue) => {
                this.loadFromValue();
            }, this);

            this.sectors.subscribe((newSectors) => {
                console.log('Sectors array changed:', newSectors);
                this.saveToValue();
            }, this);

            return this;
        },

        loadFromValue() {
            const raw = this.value() || '[]';
            console.log('Loading from value:', raw);
            try {
                const arr = JSON.parse(raw);
                const sectors = arr.map((data) => createSector(data, this.generateUniqueId.bind(this), this.defaultSector));
                this.sectors(sectors);
            } catch (e) {
                console.error('Invalid JSON in wheel_config:', e);
                this.sectors([]);
                this.value('[]');
            }
        },

        saveToValue() {
            const sectorsPlain = this.sectors().map((sector) => ({
                id: sector.id(),
                label: sector.label(),
                rule_id: sector.rule_id(),
                probability: Number(sector.probability()),
                text_color: sector.text_color(),
                result_text: sector.result_text(),
                background_color: sector.background_color(),
                border_color: sector.border_color(),
            }));
            const jsonValue = JSON.stringify(sectorsPlain);

            this.value(jsonValue);

            if (this.source && typeof this.source.set === 'function') {
                this.source.set('data.wheel_config', jsonValue);
            } else {
                console.warn('Source is not available or set method is missing');
            }
        },

        generateUniqueId() {
            return `sector_${Date.now()}_${Math.floor(Math.random() * 1000)}`;
        },

        openAddSectorPopup() {
            console.log('Opening Add Sector popup');
            this.isEditing(false);
            const newSector = createSector(null, this.generateUniqueId.bind(this), this.defaultSector);
            this.popupSector(newSector);
            this.isPopupVisible(true);
        },

        editSector(sector) {
            console.log('Editing sector:', sector);
            this.isEditing(true);
            this.popupSector(sector);
            this.isPopupVisible(true);
        },

        savePopupSector(sector) {
            const label = sector.label();
            const probability = Number(sector.probability());

            console.log('Saving sector:', { label, probability });

            if (!label) {
                alert('Label is required.');
                return;
            }
            if (isNaN(probability) || probability < 0 || probability > 100) {
                alert('Probability must be a number between 0 and 100.');
                return;
            }

            if (!this.isEditing()) {
                console.log('Adding new sector to sectors');
                this.sectors.push(sector);
            } else {
                console.log('Sector updated (editing mode):', sector);
            }

            this.closePopup();
            this.saveToValue();
        },

        removeSector(sector) {
            console.log('Removing sector:', sector);
            this.sectors.remove(sector);
            this.saveToValue();
        },

        closePopup() {
            console.log('Closing popup');
            this.isPopupVisible(false);
            this.popupSector(null);
        },
    });
});