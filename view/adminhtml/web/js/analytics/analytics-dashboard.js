define(['jquery', 'uiComponent', 'knockout', 'uiRegistry'], function ($, Component, ko, registry) {
    'use strict';

    return Component.extend({
        defaults: {
            dateFrom: '',
            dateTo: '',
            items: [],
            filteredItems: [],
            listens: {
                '${ $.provider }:data.items': 'onDataChange'
            },
            exports: {
                filteredItems: '${$.index}:filteredItems'
            }
        },

        initialize: function () {
            this._super();
            console.log('AnalyticsDashboard initialized', this.name, 'at:', new Date().toISOString());

            this.initObservable();

            this.set('loading', false);
            return this;
        },

        initObservable: function () {
            this._super().observe(['dateFrom', 'dateTo', 'items', 'filteredItems']);

            return this;
        },

        onDataChange: function (items) {
            console.log('items:>>>>>', items);
            var items = items && items ? items : [];
            var filteredItems = this.filterItemsByDateRange(items);
            
            this.items(items);
            this.filteredItems(filteredItems);
        },

        filterItemsByDateRange: function (items) {
            var dateFrom = this.dateFrom();
            var dateTo = this.dateTo();

            if (!Array.isArray(items)) {
                console.warn('Items is not an array:', items);
                return [];
            }

            if (!dateFrom && !dateTo) {
                return items;
            }

            var fromDate = dateFrom ? new Date(dateFrom) : null;
            var toDate = dateTo ? new Date(dateTo) : null;

            if (fromDate && toDate && fromDate > toDate) {
                console.warn('Invalid date range: dateFrom is after dateTo');
                return items;
            }

            if (toDate) {
                toDate.setHours(23, 59, 59, 999);
            }

            return items.filter(function (item) {
                if (!item.created_at) {
                    console.warn('Item missing created_at:', item);
                    return false;
                }
                var itemDate = new Date(item.created_at);
                var afterFrom = !fromDate || itemDate >= fromDate;
                var beforeTo = !toDate || itemDate <= toDate;
                return afterFrom && beforeTo;
            });
        },

        onDateRangeChange: function () {
            this.filteredItems(this.filterItemsByDateRange(this.items()));

            console.log('Date range changed via input:', this.filteredItems(), this.dateFrom(), this.dateTo());
        }
    });
});
