define(['Magento_Ui/js/form/element/abstract', 'ko'], (Abstract, ko) => {
    'use strict';

    const PREVIEW_SIZE = 320;
    const OUTER_RING_WIDTH = 10;
    const MIN_WHEEL_RADIUS = 100;
    const DEFAULT_COLORS = ['rgba(255, 69, 0, 0.9)', '#FFD700', '#018749'];
    const DEFAULT_TEXT_COLOR = '#111827';
    const DEFAULT_BORDER_COLOR = '#FFFFFF';
    const DEFAULT_CENTER_COLOR = '#FFFFFF';
    const DEFAULT_POINTER_COLOR = '#018749';

    const toRadians = (degrees) => (degrees * Math.PI) / 180;

    const safeText = (value, fallback = '') => {
        const text = String(value == null ? '' : value).replace(/\s+/g, ' ').trim();

        return text || fallback;
    };

    const escapeHtml = (value) => String(value == null ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');

    const approximateTextWidth = (text, fontSize) => Math.max(1, String(text).length * fontSize * 0.58);

    const truncateText = (text, fontSize, maxWidth) => {
        const value = safeText(text);
        const maxChars = Math.max(3, Math.floor(maxWidth / (fontSize * 0.58)));

        if (value.length <= maxChars) {
            return value;
        }

        return `${value.slice(0, Math.max(1, maxChars - 3)).trimEnd()}...`;
    };

    const wrapText = (text, fontSize, maxWidth) => {
        const value = safeText(text);
        const words = value.split(' ').filter(Boolean);

        if (!words.length) {
            return [''];
        }

        const lines = [];
        let currentLine = '';

        words.forEach((word) => {
            const candidate = currentLine ? `${currentLine} ${word}` : word;

            if (approximateTextWidth(candidate, fontSize) <= maxWidth) {
                currentLine = candidate;
                return;
            }

            if (currentLine) {
                lines.push(currentLine);
            }

            if (approximateTextWidth(word, fontSize) <= maxWidth) {
                currentLine = word;
                return;
            }

            lines.push(truncateText(word, fontSize, maxWidth));
            currentLine = '';
        });

        if (currentLine) {
            lines.push(currentLine);
        }

        return lines.length ? lines : [truncateText(value, fontSize, maxWidth)];
    };

    const createSectorPath = (startAngle, endAngle, radius, cx, cy) => {
        if (Math.abs(endAngle - startAngle) >= 360) {
            return `M ${cx} ${cy} m -${radius} 0 a ${radius} ${radius} 0 1 0 ${radius * 2} 0 a ${radius} ${radius} 0 1 0 -${radius * 2} 0`;
        }

        const largeArcFlag = endAngle - startAngle > 180 ? 1 : 0;
        const x1 = cx + radius * Math.cos(toRadians(startAngle));
        const y1 = cy + radius * Math.sin(toRadians(startAngle));
        const x2 = cx + radius * Math.cos(toRadians(endAngle));
        const y2 = cy + radius * Math.sin(toRadians(endAngle));

        return `M ${cx} ${cy} L ${x1} ${y1} A ${radius} ${radius} 0 ${largeArcFlag} 1 ${x2} ${y2} Z`;
    };

    const buildTextLayout = (label, radius, angle, cx, cy) => {
        let fontSize = Math.max(radius * 0.12, 12);
        const maxWidth = radius * 0.6;
        const maxLines = 3;
        let lines = wrapText(label, fontSize, maxWidth);
        let iterations = 0;

        while (lines.length > maxLines && fontSize > 10 && iterations < 4) {
            fontSize = Math.max(10, fontSize * 0.9);
            lines = wrapText(label, fontSize, maxWidth);
            iterations += 1;
        }

        if (lines.length > maxLines) {
            lines = lines.slice(0, maxLines);
            lines[maxLines - 1] = truncateText(lines[maxLines - 1], fontSize, maxWidth);
        }

        const textRadius = radius * 0.65;
        const textX = cx + textRadius * Math.cos(toRadians(angle));
        const textY = cy + textRadius * Math.sin(toRadians(angle));
        const lineHeight = fontSize * 1.2;
        const textLines = lines.map((line, index) => ({
            text: line,
            dy: index === 0 ? -((lines.length - 1) * lineHeight) / 2 : lineHeight,
        }));

        return {
            textX,
            textY,
            textTransform: `rotate(${angle}, ${textX}, ${textY})`,
            fontSize,
            fontWeight: '700',
            textLines,
        };
    };

    const buildPreviewModel = (sectors) => {
        const items = Array.isArray(sectors) ? sectors : [];
        const totalSectors = items.length;
        const wheelRadius = Math.max((PREVIEW_SIZE / 2) * 0.9, MIN_WHEEL_RADIUS);
        const totalRadius = wheelRadius + OUTER_RING_WIDTH;
        const center = totalRadius;

        if (!totalSectors) {
            return {
                hasSectors: false,
                sectors: [],
                viewBox: `0 0 ${totalRadius * 2} ${totalRadius * 2}`,
                viewSize: totalRadius * 2,
                center,
                totalRadius,
                wheelRadius,
                outerRingWidth: OUTER_RING_WIDTH,
                borderWidth: Math.max(1, wheelRadius * 0.005),
                pointerSize: wheelRadius * 0.2,
                pointerTopOffset: OUTER_RING_WIDTH * 0.5,
                centerColor: DEFAULT_CENTER_COLOR,
                pointerColor: DEFAULT_POINTER_COLOR,
                outerRingColor: DEFAULT_BORDER_COLOR,
                fontSize: Math.max(wheelRadius * 0.12, 12),
            };
        }

        const sectorAngle = 360 / totalSectors;

        return {
            hasSectors: true,
            sectors: items.map((sector, index) => {
                const label = safeText(typeof sector.label === 'function' ? sector.label() : sector.label, `Sector ${index + 1}`);
                const backgroundColor = safeText(
                    typeof sector.background_color === 'function' ? sector.background_color() : sector.background_color,
                    DEFAULT_COLORS[index % DEFAULT_COLORS.length]
                );
                const borderColor = safeText(
                    typeof sector.border_color === 'function' ? sector.border_color() : sector.border_color,
                    DEFAULT_BORDER_COLOR
                );
                const textColor = safeText(
                    typeof sector.text_color === 'function' ? sector.text_color() : sector.text_color,
                    DEFAULT_TEXT_COLOR
                );
                const startAngle = index * sectorAngle - 90;
                const endAngle = (index + 1) * sectorAngle - 90;
                const angle = startAngle + sectorAngle / 2;

                return {
                    label,
                    fill: backgroundColor,
                    stroke: borderColor,
                    textFill: textColor,
                    pathD: createSectorPath(startAngle, endAngle, wheelRadius, center, center),
                    borderWidth: Math.max(1, wheelRadius * 0.005),
                    ...buildTextLayout(label, wheelRadius, angle, center, center),
                };
            }),
            viewBox: `0 0 ${totalRadius * 2} ${totalRadius * 2}`,
            viewSize: totalRadius * 2,
            center,
            totalRadius,
            wheelRadius,
            outerRingWidth: OUTER_RING_WIDTH,
            borderWidth: Math.max(1, wheelRadius * 0.005),
            pointerSize: wheelRadius * 0.2,
            pointerTopOffset: OUTER_RING_WIDTH * 0.5,
            centerColor: DEFAULT_CENTER_COLOR,
            pointerColor: DEFAULT_POINTER_COLOR,
            outerRingColor: DEFAULT_BORDER_COLOR,
            fontSize: Math.max(wheelRadius * 0.12, 12),
        };
    };

    const createEmptyPreviewModel = () => {
        const wheelRadius = Math.max((PREVIEW_SIZE / 2) * 0.9, MIN_WHEEL_RADIUS);
        const totalRadius = wheelRadius + OUTER_RING_WIDTH;

        return {
            hasSectors: false,
            sectors: [],
            viewBox: `0 0 ${totalRadius * 2} ${totalRadius * 2}`,
            viewSize: totalRadius * 2,
            center: totalRadius,
            totalRadius,
            wheelRadius,
            outerRingWidth: OUTER_RING_WIDTH,
            borderWidth: Math.max(1, wheelRadius * 0.005),
            pointerSize: wheelRadius * 0.2,
            pointerTopOffset: OUTER_RING_WIDTH * 0.5,
            centerColor: '#FFFFFF',
            pointerColor: '#018749',
            outerRingColor: '#FFFFFF',
            fontSize: Math.max(wheelRadius * 0.12, 12),
        };
    };

    const buildPreviewHtml = (model) => {
        if (!model || !model.hasSectors) {
            return [
                '<div class="wheel-preview wheel-preview--empty">',
                '<div class="wheel-preview__head">',
                '<div><p class="wheel-config-visual__panel-note">Live preview</p><h4 class="wheel-preview__title">Storefront wheel</h4></div>',
                '<span class="wheel-preview__badge">Empty</span>',
                '</div>',
                '<div class="wheel-preview__stage">',
                '<div class="wheel-preview__empty">',
                '<h5>Add the first sector</h5>',
                '<p>The preview updates live as soon as the first sector is saved.</p>',
                '</div>',
                '</div>',
                '</div>',
            ].join('');
        }

        const sectorsMarkup = model.sectors.map((sector) => {
            const textLinesMarkup = sector.textLines.map((line, index) => (
                `<tspan x="${sector.textX}" dy="${line.dy}">${escapeHtml(line.text)}</tspan>`
            )).join('');

            return [
                '<g>',
                `<path d="${sector.pathD}" fill="${escapeHtml(sector.fill)}" stroke="${escapeHtml(sector.stroke)}" stroke-width="${sector.borderWidth}"></path>`,
                `<text x="${sector.textX}" y="${sector.textY}" transform="${escapeHtml(sector.textTransform)}" font-size="${sector.fontSize}" font-weight="${escapeHtml(sector.fontWeight)}" text-anchor="middle" dominant-baseline="middle" style="fill: ${escapeHtml(sector.textFill)};">${textLinesMarkup}</text>`,
                '</g>',
            ].join('');
        }).join('');

        const legendMarkup = model.sectors.map((sector) => (
            `<div class="wheel-preview__legend-item"><span class="wheel-preview__legend-swatch" style="background-color: ${escapeHtml(sector.fill)}"></span><span class="wheel-preview__legend-label">${escapeHtml(sector.label)}</span></div>`
        )).join('');

        return [
            '<div class="wheel-preview">',
            '<div class="wheel-preview__head">',
            '<div><p class="wheel-config-visual__panel-note">Live preview</p><h4 class="wheel-preview__title">Storefront wheel</h4></div>',
            `<span class="wheel-preview__badge">${model.sectors.length} sectors</span>`,
            '</div>',
            '<div class="wheel-preview__stage">',
            '<div class="wheel-preview__wheel-shell">',
            `<svg class="wheel-preview__svg" viewBox="${model.viewBox}" role="img" aria-label="Wheel preview">`,
            `<circle cx="${model.center}" cy="${model.center}" r="${model.totalRadius - model.outerRingWidth / 2}" stroke="${model.outerRingColor}" stroke-width="${model.outerRingWidth}" fill="none"></circle>`,
            sectorsMarkup,
            `<circle cx="${model.center}" cy="${model.center}" r="${model.wheelRadius * 0.1}" fill="${model.centerColor}"></circle>`,
            '</svg>',
            `<div class="wheel-preview__pointer" style="border-left-width: ${model.pointerSize}px; border-right-width: ${model.pointerSize}px; border-bottom-width: ${model.pointerSize * 2}px; border-bottom-color: ${model.pointerColor}; top: ${model.pointerTopOffset}px;"></div>`,
            '</div>',
            `<div class="wheel-preview__legend">${legendMarkup}</div>`,
            '</div>',
            '</div>',
        ].join('');
    };

    const createSector = (data, generateUniqueId, defaultSector) => {
        const sector = data || {};

        return {
            id: ko.observable(sector.id || generateUniqueId()),
            label: ko.observable(sector.label || defaultSector.label),
            rule_id: ko.observable(sector.rule_id || defaultSector.rule_id),
            result_text: ko.observable(sector.result_text || defaultSector.result_text),
            probability: ko.observable(sector.probability || defaultSector.probability),
            text_color: ko.observable(sector.text_color || defaultSector.text_color),
            background_color: ko.observable(sector.background_color || defaultSector.background_color),
            border_color: ko.observable(sector.border_color || defaultSector.border_color),
        };
    };

    return Abstract.extend({
        defaults: {
            template: 'ui/form/field',
            defaultSector: {
                id: null,
                label: 'New Sector',
                result_text: '',
                rule_id: null,
                probability: 10,
                text_color: '#111827',
                background_color: '#FFFFFF',
                border_color: '#D1D5DB',
            },
            priceRuleOptions: [],
            imports: {
                source: '${ $.provider }:data',
            },
        },

        cartPriceRuleOptions: ko.observableArray([]),
        sectors: ko.observableArray([]),
        isPopupVisible: ko.observable(false),
        popupSector: ko.observable(null),
        isEditing: ko.observable(false),
        popupError: ko.observable(''),
        previewModel: ko.observable(createEmptyPreviewModel()),
        previewHtml: ko.observable(''),

        initialize() {
            this._super();

            this.cartPriceRuleOptions(this.priceRuleOptions || []);
            this._isSyncing = false;

            this.closePopup = this.closePopup.bind(this);
            this.savePopupSector = this.savePopupSector.bind(this);
            this.openAddSectorPopup = this.openAddSectorPopup.bind(this);
            this.editSector = this.editSector.bind(this);
            this.removeSector = this.removeSector.bind(this);
            this.refreshPreviewSubscriptions = this.refreshPreviewSubscriptions.bind(this);
            this.syncPreviewModel = this.syncPreviewModel.bind(this);

            this.previewModel(this.getPreviewModel());
            this.previewHtml(buildPreviewHtml(this.previewModel()));
            this.loadFromValue();
            this.refreshPreviewSubscriptions();
            this.syncPreviewModel();

            this.value.subscribe((newValue) => {
                if (!this._isSyncing) {
                    this.loadFromValue(newValue);
                }
            }, this);

            this.sectors.subscribe(() => {
                if (!this._isSyncing) {
                    this.saveToValue();
                }
                this.refreshPreviewSubscriptions();
                this.syncPreviewModel();
            }, this);

            return this;
        },

        loadFromValue(rawValue = null) {
            const raw = rawValue ?? this.value() ?? '[]';

            try {
                const parsed = JSON.parse(raw);
                const sectors = Array.isArray(parsed)
                    ? parsed.map((sector) => createSector(sector, this.generateUniqueId.bind(this), this.defaultSector))
                    : [];

                this._isSyncing = true;
                this.sectors(sectors);
                this.popupError('');
                this.refreshPreviewSubscriptions();
                this.syncPreviewModel();
            } catch (e) {
                this._isSyncing = true;
                this.sectors([]);
                this.value('[]');
                this.popupError('Invalid JSON in wheel configuration.');
                this.refreshPreviewSubscriptions();
                this.syncPreviewModel();
            } finally {
                this._isSyncing = false;
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

            this._isSyncing = true;
            try {
                this.value(jsonValue);

                if (this.source && typeof this.source.set === 'function') {
                    this.source.set('data.wheel_config', jsonValue);
                }
            } finally {
                this._isSyncing = false;
            }
        },

        getSectorCount() {
            return this.sectors().length;
        },

        getLinkedRuleCount() {
            return this.sectors().filter((sector) => String(sector.rule_id() || '').trim()).length;
        },

        getTotalProbability() {
            return this.sectors().reduce((sum, sector) => sum + Number(sector.probability() || 0), 0);
        },

        getProbabilityTone() {
            const total = this.getTotalProbability();

            if (total <= 0) {
                return 'neutral';
            }

            if (total === 100) {
                return 'success';
            }

            if (total > 100) {
                return 'warning';
            }

            return 'info';
        },

        getSectorRuleLabel(sector) {
            const ruleId = String(sector.rule_id() || '').trim();

            if (!ruleId) {
                return 'No rule linked';
            }

            const match = this.cartPriceRuleOptions().find((option) => String(option.value) === ruleId);

            return match ? match.label : `Rule #${ruleId}`;
        },

        getPreviewModel() {
            return buildPreviewModel(typeof this.sectors === 'function' ? this.sectors() : []);
        },

        refreshPreviewSubscriptions() {
            if (Array.isArray(this._previewSubscriptions)) {
                this._previewSubscriptions.forEach((subscription) => {
                    if (subscription && typeof subscription.dispose === 'function') {
                        subscription.dispose();
                    }
                });
            }

            this._previewSubscriptions = [];

            this.sectors().forEach((sector) => {
                ['label', 'background_color', 'border_color', 'text_color', 'probability', 'result_text', 'rule_id'].forEach((field) => {
                    if (sector[field] && typeof sector[field].subscribe === 'function') {
                        this._previewSubscriptions.push(sector[field].subscribe(this.syncPreviewModel));
                    }
                });
            });
        },

        syncPreviewModel() {
            const model = this.getPreviewModel();
            this.previewModel(model);
            this.previewHtml(buildPreviewHtml(model));
        },

        generateUniqueId() {
            return `sector_${Date.now()}_${Math.floor(Math.random() * 1000)}`;
        },

        openAddSectorPopup() {
            this.isEditing(false);
            this.popupError('');
            this.popupSector(createSector(null, this.generateUniqueId.bind(this), this.defaultSector));
            this.isPopupVisible(true);
        },

        editSector(sector) {
            this.isEditing(true);
            this.popupError('');
            this.popupSector(sector);
            this.isPopupVisible(true);
        },

        savePopupSector(sector) {
            const label = sector.label().trim();
            const probability = Number(sector.probability());

            if (!label) {
                this.popupError('Label is required.');
                return;
            }

            if (Number.isNaN(probability) || probability < 0 || probability > 100) {
                this.popupError('Probability must be a number between 0 and 100.');
                return;
            }

            this.popupError('');

            if (!this.isEditing()) {
                this.sectors.push(sector);
            }

            this.closePopup();
            this.saveToValue();
            this.refreshPreviewSubscriptions();
            this.syncPreviewModel();
        },

        removeSector(sector) {
            this.sectors.remove(sector);
            this.saveToValue();
            this.refreshPreviewSubscriptions();
            this.syncPreviewModel();
        },

        handleEmptyStateKeydown(data, event) {
            const key = event && event.key;

            if (key === 'Enter' || key === ' ') {
                event.preventDefault();
                this.openAddSectorPopup();
            }

            return true;
        },

        closePopup() {
            this.isPopupVisible(false);
            this.popupSector(null);
            this.popupError('');
        },
    });
});
