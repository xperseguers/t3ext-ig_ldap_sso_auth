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

/**
 * Module: TYPO3/CMS/IgLdapSsoAuth/Import
 */
define([
    'jquery'
], function ($) {
    'use strict';

    var IgLdapSsoAuthImport = {
        fields: {
            form: null
        }
    };

    IgLdapSsoAuthImport.initialize = function () {
        IgLdapSsoAuthImport.fields.form = $('#tx-igldapssoauth-importform');

        $('button').click(function () {
            $('#tx-igldapssoauth-dn').val($(this).val());
        });

        IgLdapSsoAuthImport.fields.form.submit(function (e) {
            e.preventDefault(); // this will prevent from submitting the form
            var dn = $('#tx-igldapssoauth-dn').val();
            dn = dn.replaceAll('\\', '\\\\');
            IgLdapSsoAuthImport.ldapImport($("button[value='" + dn + "']").closest('tr'));
        });
    };

    IgLdapSsoAuthImport.ldapImport = function (row) {
        var self = IgLdapSsoAuthImport;
        var action = self.fields.form.data('ajaxaction');

        // Deactivate the button
        row.find('button').prop('disabled', true);

        $.ajax({
            url: TYPO3.settings.ajaxUrls[action],
            data: self.fields.form.serialize()
        }).done(function (data) {
            if (data.success) {
                row.removeClass().addClass('local-ldap-user-or-group');
                row.find('td.col-icon span').prop('title', 'id=' + data.id);
                row.find('td').removeClass('future-value');
                row.find('button').hide(400, 'linear');
            } else {
                row.find('button').prop('disabled', false);
                alert(data.message);
            }
        });
    };

    $(IgLdapSsoAuthImport.initialize);

    return IgLdapSsoAuthImport;
});
