$(function () {
    var frmData = new FormData();
    makeAjaxRequest(
        script,
        frmData,
        function (jdata) {

            if (jdata.st === 1) {
                if (!jdata.users) {
                    $('<tr>').append(
                        $('<td colspan="3" style="text-align: center">').text(jdata.msg)
                    ).appendTo("#product-table tbody");
                } else {
                    $.each(jdata.users, function (i, user) {
                        $('<tr>').append(
                            $('<td>').text(user.first_name + " " + user.last_name),
                            $('<td>').text(user.duration),
                            $('<td>').text(user.remote_addr)
                        ).appendTo("#product-table tbody");
                    });
                }
            } else {
                alert(jdata.msg);
            }
        }
    );
});