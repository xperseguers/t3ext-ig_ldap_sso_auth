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

import DocumentService from '@typo3/core/document-service.js';

class Import {
    create(options) {
        DocumentService.ready().then(() => {
            this.options = options || {};
            this.initialize();
        });
    }

    initialize() {
        document.querySelectorAll('button').forEach(function (button) {
            button.addEventListener('click', function () {
                document.getElementById('tx-igldapssoauth-dn').value = button.value;
            });
        });

        document.getElementById('tx-igldapssoauth-importform').addEventListener('submit', function (event) {
            event.preventDefault();

            let dn = document.getElementById('tx-igldapssoauth-dn').value;
            dn = dn.replaceAll('\\', '\\\\');

            this.ldapImport(document.querySelector('button[value="' + dn + '"]').closest('tr'));
        }.bind(this));
    }

    ldapImport(row) {
        const action = document.getElementById('tx-igldapssoauth-importform').getAttribute('data-ajaxaction');

        row.querySelectorAll('button').forEach(function (button) {
            button.disabled = true;
        });

        fetch(TYPO3.settings.ajaxUrls[action], {
            method: 'POST',
            body: new URLSearchParams(new FormData(document.getElementById('tx-igldapssoauth-importform')))
        }).then(function (response) {
            return response.json();
        }).then(function (data) {
            if (data.success) {
                row.classList.remove();
                row.classList.add('local-ldap-user-or-group');
                row.querySelector('td.col-icon span').title = 'id=' + data.id;
                row.querySelectorAll('td').forEach(function (td) {
                    td.classList.remove('future-value');
                });
                row.querySelectorAll('button').forEach(function (button) {
                    button.style.display = 'none';
                });
            } else {
                row.querySelectorAll('button').forEach(function (button) {
                    button.disabled = false;
                });
            }
        });
    }
}

export default new Import();
