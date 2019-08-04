(function (window, document, $, undefined) {
    $(function () {

        $('#user_list').DataTable({
            responsive: true,
            "processing": true,
            "serverSide": true,
            "bInfo": false,
            "ajax": {
                "url": script,
                "type": "POST"
            },
            "columns": [
                {"data": "login_name"},
                {"data": "names"},
                {"data": "email"},
                {"data": "msisdn"},
                {
                    "data": "rights", "fnCreatedCell": function (nTd) {
                    $(nTd).addClass('text-center');
                }
                },
                {"data": "creation_date"},
                {
                    "data": "status", "fnCreatedCell": function (nTd) {
                    $(nTd).addClass('text-center');
                }
                },
                {
                    "data": "path", "fnCreatedCell": function (nTd, sData, oData, iCol, iRow) {
                    $(nTd).addClass('text-center');
                    $(nTd).html('<a class="btn btn-light" href="' + base_url + '/users/' + sData + '"><span class="fa fa-eye"></span></a>');
                }
                }
            ]
        });
    });
})(window, document, window.jQuery);