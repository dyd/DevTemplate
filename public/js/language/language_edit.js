(function (window, document, $, undefined) {
    $(function () {
        $("input[name='is_active']").bootstrapSwitch({
            //onText: "M",
            // offText: "Y",
            //onColor: "primary",
            // offColor: "primary"
        });

        $('#submit-button').on('click', function (e) {
            e.preventDefault();

            var button = $(this);
            button.button('loading');
            var form = $('#edit-language-form');
            var formData = new FormData(form[0]);
            formData.append('id', langId);
            formData.append('is_active', $("input[name='is_active']").bootstrapSwitch('state'));

            makeAjaxRequest(
                form.attr('action'),
                formData,
                function (data) {
                    if (data.st === 1) {
                        showModal(success, successEdit, '');
                    }
                },
                function () {
                    button.button('reset');
                }
            )
        });
    });

    // On change listener for "Language flag"
    $('#languageFlag').on('change', function () {
        var fileName = $(this).val().split('\\')[2];
        $(this).next().html(fileName);

        // Read image and replace
        if (this.files && this.files[0]) {
            var reader = new FileReader();

            reader.onload = function (e) {
                $('#currentFlag').attr('src', e.target.result);
            };
            reader.readAsDataURL(this.files[0]);
        }
    });

    $('[data-toggle="popover"]').popover({
        trigger: 'hover'
    });
})(window, document, window.jQuery, null);


