(function (window, document, $, undefined) {
    $(function () {

        $('#submit-button').on('click', function (e) {
            e.preventDefault();

            var button = $(this);
            button.button('loading');
            var form = $('#add-language-form');
            var formData = new FormData(form[0]);

            makeAjaxRequest(
                form.attr('action'),
                formData,
                function (data) {
                    if (data.st === 1) {
                        showModal(success, successAdd, base_self);
                    }
                },
                function () {
                    button.button('reset');
                }
            );
        });
    });

    // On change listener for "Language flag"
    $('#languageFlag').on('change', function () {
        var fileName = $(this).val().split('\\')[2];
        $(this).next().html(fileName);
    });

    $('[data-toggle="popover"]').popover({
        trigger: 'hover'
    });
})(window, document, window.jQuery, null);
