/**
 * Created by dnatzkin on 7/31/2017.
 */
(function (window, document, $, undefined) {
    $(function () {
        $("#usr_frm").on("submit", function (e) {
            e.preventDefault();
            var btn = $('#sbm_button').button('loading');
            var frmData = new FormData(document.querySelector('#usr_frm'));
            frmData.append("modules", $("select[name='modules']").selectpicker('val'));
            frmData.append("default_language", $("select[name='default_language']").selectpicker('val'));

            makeAjaxRequest(script, frmData,
                function (jdata) {

                    if (jdata.st == '1') {

                        showModal(success, successAdd, base_self);

                    }
                    if (jdata.st == '3') {

                        showModalWarnings(jdata.msg);

                    }

                    if (jdata.st == '0') {
                        var txt = '';

                        $.each(jdata, function (Rkey, Rvalue) {
                            switch (Rkey) {
                                case 'modules':
                                    txt = txt + '<h4>' + fieldErrorModules1 + '</h4>' + fieldErrorModules2;
                                    break;
                                case 'right':
                                    txt = txt + '<h4>' + fieldErrorType1 + '</h4>' + fieldErrorType2;
                                    break;
                                case 'notsent':
                                    txt = txt + '<h4>' + fieldErrorEmail1 + '</h4>' + fieldErrorEmail2;
                                    break;
                                case 'language':
                                    txt = txt + '<h4>' + fieldErrorLanguage1 + '</h4>' + fieldErrorLanguage2;
                                    break;
                                default :

                                    $("input[type='text']").each(function (key, value) {
                                        if ($(value).attr("id") == Rkey) {
                                            $(this).addClass('border-danger');
                                            //$('#' + Rkey).popover('show');
                                        }
                                    });
                                    $("input[type='email']").each(function (key, value) {
                                        if ($(value).attr("id") == Rkey) {
                                            $(this).addClass('border-danger');
                                            //$('#' + Rkey).popover('show');
                                        }
                                    });
                                    if (Rkey == 'allowed_ip') {
                                        $("textarea[name='allowed_ip']").addClass('border-danger');
                                        //$("#comment").popover('show');
                                    }
                                    break;
                            }
                        });
                        if (txt != '') {
                            showModalWarnings(txt);
                        }
                    }

                }, function () {
                    btn.button('reset');
                });

        });

        $("input, textarea").on('change', function () {
            $(this).removeClass('border-danger');
        });

    });
})(window, document, window.jQuery);