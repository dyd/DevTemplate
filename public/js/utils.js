/**
 * Created by dnatzkin on 7/17/2017.
 */
(function (window, document, $, undefined) {
    $(function () {
        // $('[data-toggle="popover"]').popover();

    });
})(window, document, window.jQuery);

function showSpinner() {
    $("#spinner").show();
}

function hideSpinner() {
    $("#spinner").hide();
}

function showModalWarnings(txt) {
    var sel = $('#myModal');
    sel.find('.modal-body').html(txt);
    sel.modal('show');
}

function showModal(title, body, action) {
    var sel = $('#myModal');
    sel.find('.modal-title').html(title);
    sel.find('.modal-body').html(body);

    sel.modal('show');

    if (action == '') {

    } else {
        sel.find('button').attr('onClick', "parent.location.href='" + action + "'");
    }
}

function makeAjaxRequest(script, frmData, done, always) {
    showSpinner();
    $.ajax({
        url: script,
        type: "POST",
        enctype: "multipart/form-data",
        data: frmData,
        processData: false,
        contentType: false,
        success: function (jdata) {
            console.log(jdata);
            if (jdata.st == '3') {
                showModalWarnings(jdata.msg);
            } else {
                done(jdata);
            }

            /* TODO REMOVE OLD CODE

            try {

                var jdata = JSON.parse(data);

                if (jdata.st === '3') {
                    showModalWarnings(jdata.msg);
                } else {
                    done(jdata);
                }

            } catch (e) {
                alert(data);
                console.log(e);
                alert('Problem with JSON Data!');
            }*/
        },
        error: function (xhr) {
            console.log(xhr.responseText);
            console.log('Error!');
        },
        complete: function (data) {
            hideSpinner();
            if (typeof always !== "undefined") {
                always();
            }
        }
    });
}

function pophide(that) {
    $(that).parent().parent().popover('hide');
}

function getDateFromDatePicker(selector) {
    var pr_date = $(selector).datepicker('getDate');

    return ((pr_date.getDate() < 10) ? '0' + pr_date.getDate() : pr_date.getDate()) + '.' + (((pr_date.getMonth() + 1) < 10) ? '0' + (pr_date.getMonth() + 1) : (pr_date.getMonth() + 1)) + '.' + pr_date.getFullYear();
}