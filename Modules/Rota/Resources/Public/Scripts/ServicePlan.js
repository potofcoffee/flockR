/**
 * Created by chris on 31.05.2017.
 */


$(document).ready(function(){
    $('.edit-only').hide();
    $('.btnSaveRow').hide();

    $('#btnEditMultipleServices').on('click', function() {
        $(this).hide();
        $('.display-only').hide();
        $('.edit-only').show();
    });

    $('.btnToggleRow').on('click', function() {
        var row=$(this).data('row');
        $('.plan-row-'+row+'.display-only').toggle();
        $('.plan-row-'+row+'.edit-only').toggle();
        $('.plan-row-'+row+'.btnSaveRow').toggle();
    });
});
