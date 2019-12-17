/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'ko',
    'Magento_Ui/js/form/element/abstract'
], function (ko, FormElement) {
    'use strict';

    return FormElement.extend({
        defaults: {
            modules: {
                parent: '${ $.parentName }'
            }
        },

        /**
         * Is filter applied.
         *
         * @returns {Boolean}
         */
        isApplied: function () {
            return this.value() === true;
        },

        /**
         * Apply filter.
         */
        apply: function () {
            this.value(true);
            this.parent().apply();
        },

        /**
         * Clear filter.
         */
        clear: function () {
            this.value(null);
            this.parent().apply();
        }
    });
});
