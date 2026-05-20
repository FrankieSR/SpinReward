define(['uiComponent', 'chartJs', 'Magento_Ui/js/lib/view/utils/async'], function (
    Component,
    Chart,
    async
) {
    'use strict';

    return Component.extend({
        defaults: {
            items: [],
            chartData: null,
            isLoading: true,
            canvasId: 'spin-analytics-chart-canvas',
            listens: {
                '${ $.parentName }:filteredItems': 'onDataChange',
            },
        },

        initialize: function () {
            this._super().initObservable();
            this.observe(['items', 'chartData', 'isLoading']);

            this.chartData.subscribe(() => {
                this.initChart();
            });

            return this;
        },

        onDataChange: function(items) {
            if (items && items.length > 0) {
                this.items(items);
                this.loadChartData();
            }
        },

        loadChartData: function () {
            this.isLoading(true);
            const items = this.items();
            const guestCount = items.filter(i => i.customer_id === null).length;
            const registeredCount = items.length - guestCount;

            this.chartData({
                labels: ['Guest', 'Registered'],
                datasets: [{
                    label: 'Guest vs Registered Users',
                    data: [guestCount, registeredCount],
                    backgroundColor: ['#ff6384', '#36a2eb'],
                    borderColor: ['#ff6384', '#36a2eb'],
                    borderWidth: 1,
                    hoverOffset: 4
                }]
            });
        },

        initChart: function () {
            if (!this.chartData()) return;

            async.async({ selector: '#' + this.canvasId }, (canvas) => {
                if (!canvas) return;

                if (this.chart) this.chart.destroy();

                this.chart = new Chart(canvas.getContext('2d'), {
                    type: 'doughnut',
                    data: this.chartData(),
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { display: true }
                        }
                    }
                });
                this.isLoading(false);
            });
        }
    });
});
