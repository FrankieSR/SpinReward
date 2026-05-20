define(['jquery', 'uiComponent', 'chartJs', 'Magento_Ui/js/lib/view/utils/async'], function (
    $,
    Component,
    Chart,
    async
) {
    'use strict';

    return Component.extend({
        defaults: {
            payloadUrl: '',
            exportUrl: '',
            isLoading: true
        },

        initialize: function () {
            this._super().initObservable();

            this.observe([
                'isLoading',
                'activeTab',
                'summary',
                'funnel',
                'dailySeries',
                'wheelSeries',
                'sectorSeries',
                'deviceSeries',
                'utmSeries'
            ]);

            this.tabs = [
                {
                    id: 'overview',
                    label: 'Overview'
                },
                {
                    id: 'breakdown',
                    label: 'Breakdown'
                }
            ];

            this.activeTab('overview');
            this.summary({});
            this.funnel([]);
            this.dailySeries([]);
            this.wheelSeries([]);
            this.sectorSeries([]);
            this.deviceSeries([]);
            this.utmSeries([]);

            this.loadPayload();

            return this;
        },

        loadPayload: function () {
            var self = this;

            if (!this.payloadUrl) {
                this.isLoading(false);
                return;
            }

            this.isLoading(true);

            $.getJSON(this.payloadUrl)
                .done(function (response) {
                    self.summary(response.summary || {});
                    self.funnel(response.funnel || []);
                    self.dailySeries(response.daily_series || []);
                    self.wheelSeries(response.wheel_series || []);
                    self.sectorSeries(response.sector_series || []);
                    self.deviceSeries(response.device_series || []);
                    self.utmSeries(response.utm_series || []);
                    if (self.activeTab() === 'overview') {
                        self.deferRenderCharts();
                    }
                })
                .fail(function () {
                    self.summary({});
                    self.funnel([]);
                    self.dailySeries([]);
                    self.wheelSeries([]);
                    self.sectorSeries([]);
                    self.deviceSeries([]);
                    self.utmSeries([]);
                    self.destroyCharts();
                })
                .always(function () {
                    self.isLoading(false);
                });
        },

        reloadPayload: function () {
            this.loadPayload();
        },

        activateTab: function (tabId) {
            if (this.activeTab() === tabId) {
                return;
            }

            this.activeTab(tabId);

            if (tabId === 'overview') {
                this.deferRenderCharts();
                return;
            }

            this.destroyCharts();
        },

        deferRenderCharts: function () {
            var self = this;

            window.setTimeout(function () {
                self.renderCharts();
            }, 0);
        },

        destroyCharts: function () {
            if (this.chartDaily) {
                this.chartDaily.destroy();
                this.chartDaily = null;
            }

            if (this.chartWheel) {
                this.chartWheel.destroy();
                this.chartWheel = null;
            }
        },

        renderCharts: function () {
            this.destroyCharts();

            var self = this;

            async.async({ selector: '#wishreward-analytics-daily-chart' }, function (canvas) {
                if (canvas) {
                    self.renderLineChart(canvas, self.dailySeries());
                }
            });

            async.async({ selector: '#wishreward-analytics-wheel-chart' }, function (canvas) {
                if (canvas) {
                    self.renderBarChart(canvas, self.wheelSeries(), 'Top wheels by spins');
                }
            });
        },

        renderLineChart: function (canvas, series) {
            if (this.chartDaily) {
                this.chartDaily.destroy();
            }

            this.chartDaily = new Chart(canvas.getContext('2d'), {
                type: 'line',
                data: {
                    labels: series.map(function (row) {
                        return row.label;
                    }),
                    datasets: [{
                        label: 'Spins',
                        data: series.map(function (row) {
                            return row.value;
                        }),
                        borderColor: '#334155',
                        backgroundColor: 'rgba(51, 65, 85, 0.08)',
                        tension: 0.28,
                        fill: true,
                        pointRadius: 2,
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { color: '#71717a' }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                                color: '#71717a'
                            },
                            grid: {
                                color: '#ececf1'
                            }
                        }
                    }
                }
            });
        },

        renderBarChart: function (canvas, series, label) {
            if (this.chartWheel) {
                this.chartWheel.destroy();
            }

            this.chartWheel = new Chart(canvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: series.map(function (row) {
                        return row.label;
                    }),
                    datasets: [{
                        label: label,
                        data: series.map(function (row) {
                            return row.value;
                        }),
                        backgroundColor: '#475569',
                        borderRadius: 8,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { color: '#71717a' }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                                color: '#71717a'
                            },
                            grid: {
                                color: '#ececf1'
                            }
                        }
                    }
                }
            });
        },

        safeNumber: function (value) {
            return Number(value) || 0;
        },

        ratio: function (numerator, denominator) {
            var total = Number(denominator) || 0;

            if (!total) {
                return 0;
            }

            return (Number(numerator) || 0) / total;
        },

        ctaRate: function () {
            var summary = this.summary() || {};

            return this.ratio(summary.cta_clicks, summary.popup_impressions);
        },

        validationRate: function () {
            var summary = this.summary() || {};

            return this.ratio(summary.spin_validated, summary.spin_submits);
        },

        winRate: function () {
            var summary = this.summary() || {};

            return this.ratio(summary.wins, summary.spin_validated);
        },

        couponRate: function () {
            var summary = this.summary() || {};

            return this.ratio(summary.coupons_generated, summary.wins || summary.spin_validated);
        },

        orderRate: function () {
            var summary = this.summary() || {};

            return this.ratio(summary.orders_count, summary.coupons_generated);
        },

        formatPercent: function (value) {
            return new Intl.NumberFormat(undefined, {
                style: 'percent',
                maximumFractionDigits: 1
            }).format(Number(value) || 0);
        },

        formatNumber: function (value) {
            return new Intl.NumberFormat().format(Number(value) || 0);
        },

        formatCurrency: function (value) {
            return new Intl.NumberFormat(undefined, {
                style: 'currency',
                currency: 'EUR',
                maximumFractionDigits: 2
            }).format(Number(value) || 0);
        },

        exportCsv: function () {
            if (!this.exportUrl) {
                return;
            }

            window.location.href = this.exportUrl;
        },

        destroy: function () {
            if (this.chartDaily) {
                this.chartDaily.destroy();
                this.chartDaily = null;
            }

            if (this.chartWheel) {
                this.chartWheel.destroy();
                this.chartWheel = null;
            }

            return this._super();
        }
    });
});
