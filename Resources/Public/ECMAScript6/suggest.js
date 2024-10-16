/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

import FormEngineValidation from '@typo3/backend/form-engine-validation.js';

class SuggestButton {
    constructor(options) {
        setTimeout(function () {
            this.initializeButton(options);
        }.bind(this), 500);
    }
    
    initializeButton(options) {
        const that = this;
        document.getElementById(options.suggestId + '_btn')
            .addEventListener('click', function (e) {
                e.preventDefault();

                const field = document.querySelector('*[data-formengine-input-name="' + options.fieldName + '"]'),
                    hiddenField = document.getElementsByName(options.fieldName)[0];
                field.value = document.getElementById(options.suggestId).innerText;
                hiddenField.value = field.value;
                FormEngineValidation.markFieldAsChanged(field);
            });
    }
}

export default SuggestButton;
