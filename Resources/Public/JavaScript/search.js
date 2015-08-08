IgLdapSsoAuthSearch = {

    $: null,
    type: 'fe_users',

    fields: {
        form: null,
        basedn: null,
        filter: null,
        result: null
    },

    actions: {
        updateForm: null
    },

    updateForm: function (type) {
        var self = IgLdapSsoAuthSearch;
        self.type = type;

        self.$.ajax({
            url: self.actions.updateForm
                .replace(/TYPE/, type)
        }).done(function (data) {
            if (data.success) {
                self.fields.basedn.val(data.configuration.basedn);
                self.fields.filter.val(data.configuration.filter);
                self.search();
            }
        });
    },

    search: function () {
        var self = IgLdapSsoAuthSearch;

        self.$.ajax({
            type: 'POST',
            url: self.fields.form.prop('action'),
            data: self.fields.form.serialize()
        }).done(function (data) {
            if (data.success) {
                self.fields.result.html(data.html);
            }
        });
    }

};

// IIFE for faster access to $ and safe $ use
(function ($) {
    $(document).ready(function () {
        IgLdapSsoAuthSearch.$ = $;
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
    });
}(jQuery));
