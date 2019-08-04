(function (window, document, $, undefined) {
    $(function () {
        $('#watchers').DataTable({
            responsive: true,
            "processing": true,
            "serverSide": true,
            "bInfo": false,
            "bFilter": false,
            "caption-side": "top",
            "bSort": true,
            "lengthChange": false,
            "columnDefs": [
                {
                    "targets": [2],
                    "orderable": false
                }
            ],
            "ajax": {
                "url": script,
                "type": "POST",
                "data": {}
            },
            "columns": [
                {"data": "languages"},
                {"data": "language_code"},
                {
                    "data": "id",
                    "fnCreatedCell": function (nTd, sData, oData, iCol, iRow) {
                        $(nTd).addClass('text-center');
                        $(nTd).html('<a class="btn btn-light" href="' + base_url + '/translations/edit/' + sData + '">' +
                            '<i class="text-warning fa fa-pencil"></i>' +
                            '</a>' +
                            ' <button class="btn btn-light" onclick="deleteTranslation(' + sData + ');">' +
                            '<i class="text-danger fa fa-trash"></i>' +
                            '</button>'
                        );
                    }
                }
            ]
        });
    });
})(window, document, window.jQuery, null);


function deleteTranslation(id) {
    var frmData = new FormData();
    frmData.append('id', id);

    makeAjaxRequest(
        deleteScript,
        frmData,
        function (data) {
            if (data.st === 1) {
                showModal(success, successDel, base_self);
            }
        }
    );

    return false;
}