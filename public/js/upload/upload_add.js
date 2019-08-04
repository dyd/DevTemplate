/**
 * Created by dnatzkin on 5/8/2018.
 */
(function (window, document, $, undefined) {
    $(function () {

        $("input[name='date_on_action']").datepicker({
            orientation: "top",
            format: "dd.mm.yyyy",
            startView: 0,
            minViewMode: 0,
            maxViewMode: 1,
            inline: false,
        });

        $('#file').on("change", function (e) {
            $('.custom-file-control').text($(this)[0].files[0].name);
        });

        $('#sbm_btn').on('click', function (e) {
            e.preventDefault();
            
            var frmData = new FormData();

            frmData.append('date_on_action', getDateFromDatePicker('#date_on_action'));

            frmData.append('file', $('#file')[0].files[0]);
            frmData.append('description', $('textarea[name="description"]').val());

            makeAjaxRequest(
                '/post/forms/upload/upload_add.php',
                frmData,
                function (data) {
                    if (data.st == 1) {
                        showModal(success, successUpload, base_self);
                    }
                },
                function () {
                    $('#file').val('');
                    $('.custom-file-control').html('&nbsp;');
                }
            );

        });

    });
})(window, document, window.jQuery);
