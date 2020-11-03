define([
    'Magento_Ui/js/form/element/abstract'
], function (Abstract) {
    'use strict';

    return Abstract.extend({
        defaults: {
            cols: 15,
            rows: 10,
            elementTmpl: 'ui/form/element/textarea'
        },

        /**
         * Converts value to JSON.
         *
         * @returns {String}
         */
        normalizeData: function () {
            return JSON.stringify(JSON.parse(this._super()), null, 4);
        },
    });
});
