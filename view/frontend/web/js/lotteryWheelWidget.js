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

            // Add window resize event handler
            $(window).on('resize', this._onResize.bind(this));
        },

        /**
         * Calculates adaptive dimensions based on the container's size.
         */
        _calculateDimensions: function () {
            const $container = this.element;
            const containerWidth = $container.width();
            const containerHeight = $container.height();

            // Use the smaller dimension (width or height) of the container as the base size
            const baseSize = Math.min(containerWidth, containerHeight);

            // Calculate the wheel radius from the container size and ensure a minimum size of 100
            this.options.wheelRadius = (baseSize / 2) * 0.9;
            if (this.options.wheelRadius < 100) {
                this.options.wheelRadius = 100;
            }

            // Set the outer ring width (constant value in this example)
            this.options.outerRingWidth = 10;

            // Scale the font size based on the wheel radius
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
        _applyStyleTheme: function () {
            this.options.items.forEach((item, index) => {
                item.sectorBG = item.background_color || this.options.colors[index % this.options.colors.length];
            });
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
                textColor,
                fontSize,
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

                const text = this._createSectorText(item.label, startAngle, endAngle, wheelRadius, centerX, centerY);
                text.setAttribute('fill', textColor);
                text.setAttribute('font-size', fontSize);
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
         * Creates the SVG text element for a wheel sector.
         */
        _createSectorText: function (text, startAngle, endAngle, radius, cx, cy) {
            const angle = (startAngle + endAngle) / 2;
            const textRadius = radius * 0.65;
            const x = cx + textRadius * Math.cos((angle * Math.PI) / 180);
            const y = cy + textRadius * Math.sin((angle * Math.PI) / 180);

            const textElement = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            textElement.setAttribute('x', x);
            textElement.setAttribute('y', y);
            textElement.setAttribute('text-anchor', 'middle');
            textElement.setAttribute('alignment-baseline', 'middle');
            textElement.textContent = text;

            const rotationAngle = angle;
            textElement.setAttribute('transform', `rotate(${rotationAngle}, ${x}, ${y})`);

            return textElement;
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
            const pointerSize = this.options.wheelRadius * 0.13;
            const pointerTopOffset = this.options.outerRingWidth * 0.5;

            $('<div>', {
                class: 'wheel-pointer'
            }).css({
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
            }).appendTo(this.element);
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
                        data
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
