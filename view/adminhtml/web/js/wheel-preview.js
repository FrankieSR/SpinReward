define([
    'jquery',
    'underscore',
    'uiComponent',
    'ko'
], function ($, _, Component, ko) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Doroshko_WishReward/wheel-preview',
            sectors: ko.observableArray([])
        },

        initialize: function () {
            this._super();
            this.initWheel();
        },

        initWheel: function () {
            this.canvas = document.createElement('canvas');
            this.canvas.width = 400;
            this.canvas.height = 400;
            this.ctx = this.canvas.getContext('2d');
            
            this.sectors.subscribe(this.drawWheel.bind(this));
            
            this.drawWheel();
        },

        drawWheel: function () {
            const sectors = this.sectors();
            if (!sectors.length) {
                return;
            }

            const centerX = this.canvas.width / 2;
            const centerY = this.canvas.height / 2;
            const radius = Math.min(centerX, centerY) - 10;

            this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

            let startAngle = 0;
            const totalProbability = sectors.reduce((sum, sector) => sum + parseFloat(sector.probability), 0);

            sectors.forEach(sector => {
                const angle = (sector.probability / totalProbability) * 2 * Math.PI;
                
                this.ctx.beginPath();
                this.ctx.moveTo(centerX, centerY);
                this.ctx.arc(centerX, centerY, radius, startAngle, startAngle + angle);
                this.ctx.closePath();
                this.ctx.fillStyle = sector.color;
                this.ctx.fill();
                this.ctx.stroke();

                this.ctx.save();
                this.ctx.translate(centerX, centerY);
                this.ctx.rotate(startAngle + angle / 2);
                this.ctx.textAlign = 'right';
                this.ctx.fillStyle = '#000000';
                this.ctx.font = '14px Arial';
                this.ctx.fillText(sector.label, radius - 10, 5);
                this.ctx.restore();

                startAngle += angle;
            });

            const container = document.getElementById('wheel-preview-canvas');
            if (container) {
                container.innerHTML = '';
                container.appendChild(this.canvas);
            }
        },

        getPreviewElement: function () {
            return this.canvas;
        }
    });
}); 