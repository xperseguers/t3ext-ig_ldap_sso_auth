IgLdapSsoAuthImport = {

    $: null,

    fields: {
        form: null
    },

    ldapImport: function (row) {
        var self = IgLdapSsoAuthImport;

        // Deactivate the button
        row.find('button').prop('disabled', true);

        self.$.ajax({
            type: 'POST',
            url: self.fields.form.prop('action'),
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
    }

};

// IIFE for faster access to $ and safe $ use
(function ($) {
    $(document).ready(function () {
        IgLdapSsoAuthImport.$ = $;
        IgLdapSsoAuthImport.fields.form = $('#tx-igldapssoauth-importform');

        $('button[type=submit]').click(function () {
            $('#tx-igldapssoauth-dn').val($(this).val());
        });

        IgLdapSsoAuthImport.fields.form.submit(function (e) {
            e.preventDefault(); // this will prevent from submitting the form
            var dn = $('#tx-igldapssoauth-dn').val();
            dn=dn.replace('\\','\\\\');
            IgLdapSsoAuthImport.ldapImport($("button[value='" + dn + "']").closest('tr'));
        });
    });
}(jQuery || TYPO3.jQuery));
