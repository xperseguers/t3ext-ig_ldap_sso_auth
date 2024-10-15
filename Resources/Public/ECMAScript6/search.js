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

class Search {
    create(options) {
        this.options = options || {};

        this.initialize();
    }

    initialize() {
        const that = this;

        document.getElementById('tx-igldapssoauth-searchform').addEventListener('submit', function (event) {
            event.preventDefault();

            this.search();
        }.bind(this));

        document.querySelectorAll('input[type="radio"]').forEach(function (radio) {
            radio.addEventListener('click', function () {
                that.updateForm(radio.value);
            });
        });

        document.querySelectorAll('input[type="checkbox"]').forEach(function (checkbox) {
            checkbox.addEventListener('click', function () {
                that.search();
            });
        });

        document.getElementById('tx-igldapssoauth-search').addEventListener('click', function () {
            that.search();
        });

        if (document.getElementById('tx-igldapssoauth-searchform')) {
            this.search();
        }
    }

    search() {
        fetch(TYPO3.settings.ajaxUrls['ldap_search'], {
            method: 'POST',
            body: new URLSearchParams(new FormData(document.getElementById('tx-igldapssoauth-searchform')))
        }).then(function (response) {
            return response.json();
        }).then(function (data) {
            document.getElementById('tx-igldapssoauth-result').innerHTML = data.html;
        });
    }

    updateForm(type) {
        const that = this;
        const basedn = document.getElementById('tx-igldapssoauth-basedn');
        const filter = document.getElementById('tx-igldapssoauth-filter');

        fetch(TYPO3.settings.ajaxUrls['ldap_form_update'], {
            method: 'POST',
            body: new URLSearchParams({
                configuration: document.getElementById('tx-igldapssoauth-result').getAttribute('data-configuration'),
                type: type
            })
        }).then(function (response) {
                return response.json();
        }).then(function (data) {
            if (data.success) {
                basedn.value = data.configuration.basedn;
                filter.value = data.configuration.filter;
                that.search();
            }
        });
    }
}

export default new Search();
