/**
 * Created by dnatzkin on 12/14/2017.
 */
(function(window, document, $, undefined){
    $(function(){
        $('#language_picker').on('changed.bs.select', function (e) {
            window.location.replace(base_url + '/change_language.php?id=' + $(this).selectpicker('val'));
        });
    });
})(window, document, window.jQuery, null);
