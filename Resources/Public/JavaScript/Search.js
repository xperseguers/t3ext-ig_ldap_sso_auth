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
 * Module: TYPO3/CMS/IgLdapSsoAuth/Search
 */
define([
    'jquery'
], function ($) {
    'use strict';

    var IgLdapSsoAuthSearch = {
        type: 'fe_users',

        fields: {
            form: null,
            basedn: null,
            filter: null,
            result: null
        }
    };

    IgLdapSsoAuthSearch.initialize = function () {
        IgLdapSsoAuthSearch.fields.form = $('#tx-igldapssoauth-searchform');
        IgLdapSsoAuthSearch.fields.basedn = $('#tx-igldapssoauth-basedn');
        IgLdapSsoAuthSearch.fields.filter = $('#tx-igldapssoauth-filter');
        IgLdapSsoAuthSearch.fields.result = $('#tx-igldapssoauth-result');

        IgLdapSsoAuthSearch.fields.form.submit(function (e) {
            e.preventDefault(); // this will prevent from submitting the form
            IgLdapSsoAuthSearch.search();
        });

        $(':radio').click(function () {
            IgLdapSsoAuthSearch.updateForm($(this).val());
        });

        $(':checkbox').click(function () {
            IgLdapSsoAuthSearch.search();
        });

        $('#tx-igldapssoauth-search').click(function () {
            IgLdapSsoAuthSearch.search();
        });

        if (IgLdapSsoAuthSearch.fields.form.length) {
            IgLdapSsoAuthSearch.search();
        }
    };

    IgLdapSsoAuthSearch.updateForm = function (type) {
        var self = IgLdapSsoAuthSearch;
        self.type = type;

        $.ajax({
            url: TYPO3.settings.ajaxUrls['ldap_form_update'],
            data: {
                configuration: $('#tx-igldapssoauth-result').data('configuration'),
                type: type
            }
        }).done(function (data) {
            if (data.success) {
                self.fields.basedn.val(data.configuration.basedn);
                self.fields.filter.val(data.configuration.filter);
                self.search();
            }
        });
    };

    IgLdapSsoAuthSearch.search = function () {
        var self = IgLdapSsoAuthSearch;

        $.ajax({
            url: TYPO3.settings.ajaxUrls['ldap_search'],
            data: self.fields.form.serialize()
        }).done(function (data) {
            self.fields.result.html(data.html);
        });
    };

    $(IgLdapSsoAuthSearch.initialize);

    return IgLdapSsoAuthSearch;
});
