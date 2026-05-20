define(['jquery', 'jquery/ui'], function ($) {
    'use strict';

    $.widget('doroshko.lotteryWheel', {
        options: {
            items: [],
            rotationDuration: 6000,
            colors: ['rgba(255, 69, 0, 0.9)', '#FFD700', '#018749'],
            textColor: '#000',
            borderColor: '#FFFFFF',
            borderWidth: 0,
            centerColor: '#0B132B',
            pointerColor: '#018749',
            outerRingColor: '#FFFFFF',
            outerRingWidth: 10,
            fontSizeRatio: 0.12,
            fontWeight: 'bold',
            onSpinEnd: () => {},
        },

        /**
         * Initializes the widget. Sets up initial values, renders the wheel, and adds a resize handler.
         */
        _create: function () {
            this.currentRotation = 0;
            this._applyStyleTheme();
            this._calculateDimensions();
            this._renderWheel();

            $(window).on('resize', this._onResize.bind(this));
        },

        /**
         * Calculates adaptive dimensions based on the container's size.
         */
        _calculateDimensions: function () {
            const $container = this.element;
            const containerWidth = $container.width();
            const containerHeight = $container.height();
            const baseSize = Math.min(containerWidth, containerHeight);

            this.options.wheelRadius = (baseSize / 2) * 0.9;
            if (this.options.wheelRadius < 100) {
                this.options.wheelRadius = 100;
            }

            this.options.outerRingWidth = 10;
            this.options.fontSize = this.options.wheelRadius * this.options.fontSizeRatio;

            // Scale the border width ensuring a minimum value of 1
            this.options.borderWidth = Math.max(1, this.options.wheelRadius * 0.005);
        },

        /**
         * Handles the window resize event by recalculating dimensions and re-rendering the wheel.
         */
        _onResize: function () {
            this._calculateDimensions();
            this.element.empty();
            this._renderWheel();
        },

        /**
         * Applies a color theme to each wheel item.
         */
        _applyStyleTheme() {
            this.options.items = this.options.items.map((item, index) => ({
                ...item,
                sectorBG: item.background_color || this.options.colors[index % this.options.colors.length],
                borderColor: item.border_color || this.options.borderColor,
                textColor: item.text_color || this.options.textColor,
            }));
        },

        /**
         * Renders the entire wheel including the outer ring, sectors, center circle, and pointer.
         */
        _renderWheel: function () {
            const svg = this._createSVG();

            this._renderOuterRing(svg);
            this._renderSectors(svg);
            this._renderCenterCircle(svg);
            this._renderPointer();

            this.element.append(svg);
        },

        /**
         * Creates an SVG element for the wheel with adaptive dimensions.
         */
        _createSVG: function () {
            const totalRadius = this.options.wheelRadius + this.options.outerRingWidth;
            const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');

            svg.setAttribute('class', 'wheel');
            svg.setAttribute('width', '100%');
            svg.setAttribute('height', '100%');
            svg.setAttribute('viewBox', `0 0 ${totalRadius * 2} ${totalRadius * 2}`);
            svg.setAttribute('preserveAspectRatio', 'xMidYMid meet');

            return svg;
        },

        /**
         * Renders the outer ring of the wheel.
         */
        _renderOuterRing: function (svg) {
            const totalRadius = this.options.wheelRadius + this.options.outerRingWidth;
            const centerX = totalRadius;
            const centerY = totalRadius;

            const outerRing = document.createElementNS('http://www.w3.org/2000/svg', 'circle');

            outerRing.setAttribute('cx', centerX);
            outerRing.setAttribute('cy', centerY);
            outerRing.setAttribute('r', totalRadius - this.options.outerRingWidth / 2);
            outerRing.setAttribute('fill', 'none');
            outerRing.setAttribute('stroke', this.options.outerRingColor);
            outerRing.setAttribute('stroke-width', this.options.outerRingWidth);

            svg.appendChild(outerRing);
        },

        /**
         * Renders the sectors of the wheel.
         */
        _renderSectors: function (svg) {
            const {
                items,
                wheelRadius,
                borderColor,
                borderWidth,
                fontWeight
            } = this.options;
            const numItems = items.length;
            const sectorAngle = 360 / numItems;
            const centerX = wheelRadius + this.options.outerRingWidth;
            const centerY = wheelRadius + this.options.outerRingWidth;

            items.forEach((item, index) => {
                const startAngle = index * sectorAngle - 90;
                const endAngle = (index + 1) * sectorAngle - 90;

                const path = this._createSectorPath(startAngle, endAngle, wheelRadius, centerX, centerY, item.sectorBG);
                path.setAttribute('stroke', borderColor);
                path.setAttribute('stroke-width', borderWidth);
                svg.appendChild(path);

                const text = this._createSectorText(item, startAngle, endAngle, wheelRadius, centerX, centerY);
                text.setAttribute('fill', item.textColor);
                text.setAttribute('font-weight', fontWeight);
                svg.appendChild(text);
            });
        },

        /**
         * Creates the SVG path element for a wheel sector.
         */
        _createSectorPath: function (startAngle, endAngle, radius, cx, cy, color) {
            const largeArcFlag = endAngle - startAngle > 180 ? 1 : 0;
            const x1 = cx + radius * Math.cos((startAngle * Math.PI) / 180);
            const y1 = cy + radius * Math.sin((startAngle * Math.PI) / 180);
            const x2 = cx + radius * Math.cos((endAngle * Math.PI) / 180);
            const y2 = cy + radius * Math.sin((endAngle * Math.PI) / 180);

            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');

            path.setAttribute('d', `M ${cx} ${cy} L ${x1} ${y1} A ${radius} ${radius} 0 ${largeArcFlag} 1 ${x2} ${y2} Z`);
            path.setAttribute('fill', color);

            return path;
        },

        /**
         * Creates an SVG text element for a wheel sector with word wrapping and font size adjustment.
         * @param {Object} item - Sector item with label and textColor.
         * @param {number} startAngle - Start angle of the sector (degrees).
         * @param {number} endAngle - End angle of the sector (degrees).
         * @param {number} radius - Radius of the wheel.
         * @param {number} cx - X-coordinate of the wheel center.
         * @param {number} cy - Y-coordinate of the wheel center.
         * @returns {SVGElement} SVG text element with wrapped text.
         */
        _createSectorText: function (item, startAngle, endAngle, radius, cx, cy) {
            const angle = (startAngle + endAngle) / 2;
            const textRadius = radius * 0.65;
            const x = cx + textRadius * Math.cos((angle * Math.PI) / 180);
            const y = cy + textRadius * Math.sin((angle * Math.PI) / 180);

            const textElement = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            textElement.setAttribute('x', x);
            textElement.setAttribute('y', y);
            textElement.setAttribute('text-anchor', 'middle');
            textElement.setAttribute('alignment-baseline', 'middle');
            textElement.setAttribute('font-weight', this.options.fontWeight);

            const maxWidth = radius * 0.6;
            const maxLines = 3;
            const lineHeight = this.options.fontSize * 1.2;
            let fontSize = this.options.fontSize;

            let lines = this._wrapText(item.label, fontSize, maxWidth);

            if (lines.length > maxLines) {
                fontSize = Math.max(fontSize * (maxLines / lines.length), 12);
                lines = this._wrapText(item.label, fontSize, maxWidth).slice(0, maxLines);
            }

            textElement.setAttribute('font-size', fontSize);

            lines.forEach((line, index) => {
                const tspan = document.createElementNS('http://www.w3.org/2000/svg', 'tspan');
                tspan.setAttribute('x', x);
                tspan.setAttribute('dy', index === 0 ? (-(lines.length - 1) * lineHeight) / 2 : lineHeight);
                tspan.textContent = line;
                textElement.appendChild(tspan);
            });

            textElement.setAttribute('transform', `rotate(${angle}, ${x}, ${y})`);

            return textElement;
        },

        /**
         * Wraps text into lines based on estimated width.
         * @param {string} text - Text to wrap.
         * @param {number} fontSize - Font size in pixels.
         * @param {number} maxWidth - Maximum width in pixels.
         * @returns {string[]} Array of text lines.
         */
        _wrapText: function (text, fontSize, maxWidth) {
            const words = text.split(' ');
            const lines = [];
            let currentLine = [];

            words.forEach(word => {
                const testLine = [...currentLine, word].join(' ');
                const estimatedWidth = testLine.length * (fontSize * 0.6);
                if (estimatedWidth <= maxWidth) {
                    currentLine.push(word);
                } else {
                    if (currentLine.length > 0) {
                        lines.push(currentLine.join(' '));
                    }
                    currentLine = [word];
                }
            });

            if (currentLine.length > 0) {
                lines.push(currentLine.join(' '));
            }

            return lines;
        },

        /**
         * Renders the center circle of the wheel.
         */
        _renderCenterCircle: function (svg) {
            const centerX = this.options.wheelRadius + this.options.outerRingWidth;
            const centerY = this.options.wheelRadius + this.options.outerRingWidth;

            const centerCircle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            centerCircle.setAttribute('cx', centerX);
            centerCircle.setAttribute('cy', centerY);
            centerCircle.setAttribute('r', this.options.wheelRadius * 0.1);
            centerCircle.setAttribute('fill', this.options.centerColor);

            svg.appendChild(centerCircle);
        },

        /**
         * Renders the pointer element above the wheel.
         */
        _renderPointer: function () {
            const pointerSize = this.options.wheelRadius * 0.20;
            const pointerTopOffset = this.options.outerRingWidth * 0.5;

            $('<div>', {
                    class: 'wheel-pointer',
                })
                .css({
                    position: 'absolute',
                    width: 0,
                    height: 0,
                    'border-left': `${pointerSize}px solid transparent`,
                    'border-right': `${pointerSize}px solid transparent`,
                    'border-bottom': `${pointerSize * 2}px solid ${this.options.pointerColor}`,
                    top: `${pointerTopOffset}px`,
                    left: '50%',
                    transform: 'translateX(-50%) rotate(180deg)',
                    zIndex: 10,
                })
                .appendTo(this.element);
        },

        /**
         * Spins the wheel to a specific item.
         */
        spinToItem: function (id, data = {}, onSpinEndCallback) {
            const targetIndex = this.options.items.findIndex((item) => item.id === id);
            if (targetIndex === -1) {
                console.error(`Item with ID "${id}" not found.`);
                return;
            }

            const numItems = this.options.items.length;
            const sectorAngle = 360 / numItems;
            const targetAngle = 360 - (targetIndex * sectorAngle + sectorAngle / 2);

            const rotations = 5;
            const finalRotation = rotations * 360 + targetAngle;

            this._animateSpin(finalRotation, targetIndex, data, onSpinEndCallback);
        },

        /**
         * Animates the spinning of the wheel.
         */
        _animateSpin: function (finalRotation, targetIndex, data, onSpinEndCallback) {
            const duration = this.options.rotationDuration;
            const easingOut = (t) => 1 - Math.pow(1 - t, 3);

            let startTime = null;
            const startRotation = this.currentRotation;

            const animate = (timestamp) => {
                if (!startTime) {
                    startTime = timestamp;
                }
                const elapsed = timestamp - startTime;

                const t = Math.min(elapsed / duration, 1);
                const easedT = easingOut(t);
                const currentRotation = startRotation + easedT * (finalRotation - startRotation);

                this.element.find('.wheel').css('transform', `rotate(${currentRotation % 360}deg)`);

                if (t < 1) {
                    requestAnimationFrame(animate);
                } else {
                    this.currentRotation = currentRotation % 360;

                    const winningItem = this.options.items[targetIndex];
                    const result = {
                        id: winningItem.id,
                        label: winningItem.label,
                        data,
                    };

                    if (onSpinEndCallback) {
                        onSpinEndCallback(result);
                    }

                    if (typeof this.options.onSpinEnd === 'function') {
                        this.options.onSpinEnd(result);
                    }
                }
            };

            requestAnimationFrame(animate);
        },

        /**
         * Destroys the widget by removing event listeners and clearing its content.
         */
        _destroy: function () {
            $(window).off('resize', this._onResize.bind(this));
            this.element.empty();
        },
    });

    return $.doroshko.lotteryWheel;
});
