(function (window, document, $, undefined) {
    $(function () {
        $("#delete").on("click", function (e) {
            e.preventDefault();
            var btn = $("#delete").button('loading');
            var frmData = new FormData();
            frmData.append('userId', userId);

            makeAjaxRequest(script_delete, frmData,
                function (jdata) {
                    if (jdata.st === 1) {
                        showModal(success, successDel, base_self);
                    } else {
                        showModalWarnings(jdata.msg);
                    }
                },
                function () {
                    btn.button('reset');
                });
        });

        $("#password_reset").on("click", function (e) {
            e.preventDefault();
            var btn = $("#delete").button('loading');
            var frmData = new FormData();
            frmData.append('userId', userId);

            makeAjaxRequest(script_reset_password, frmData,
                function (jdata) {

                    if (jdata.st === 1) {
                        showModal(success, successResetPass, '');
                    }
                },
                function () {
                    btn.button('reset');
                });
        });
    });
})(window, document, window.jQuery);