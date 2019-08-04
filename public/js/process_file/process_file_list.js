/**
 * Created by dnatzkin on 11.5.2018 ã..
 */
(function (window, document, $, undefined) {
    $(function () {

        $('#process_list').DataTable({
            responsive: true,
            "processing": true,
            "serverSide": true,
            "bInfo": false,
            "columnDefs": [{
                "targets": [2],
                "orderable": false
            }],
            "order": [
                [1, 'desc']
            ]
            ,
            "ajax": {
                "url": base_url + "/post/forms/process_file/process_file_list.php",
                "type": "POST"
            },
            "columns": [
                {"data": "filename"},
                {"data": "date_create"},
                {"data": "description"},
                {
                    "data": "id",
                    "fnCreatedCell": function (nTd, sData, oData, iCol, iRow) {
                        $(nTd).addClass('text-center');
                        var string = '';
                        string = '<a onclick="add_execute(' + sData + ')" class="btn btn-outline-success play" href="#" data-uid="' + sData + '"><i class="fa fa-play"></i></a>';
                        $(nTd).html(string);
                    }
                }
            ]
        });


    });
})(window, document, window.jQuery);

function add_execute(id) {

    var frmData = new FormData();

    frmData.append('id', id);

    makeAjaxRequest(
        '/post/forms/process_file/process_file_add.php',
        frmData,
        function (data) {
            if (data.st == 1) {
                showModal(success, successStart, base_self);
            }
        },
        function () {

        }
    )

}
