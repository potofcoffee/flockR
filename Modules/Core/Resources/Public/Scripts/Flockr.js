$(document).ready(function () {

    // data tables
    $('.data-table').DataTable({
        "language": {
            url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/German.json",
            buttons: {
                colvis: 'Angezeigte Spalten'
            }
        },
        dom: 'Bfrtip',
        buttons: [
            'colvis'
        ],
        colReorder: true
    });


    // action tables
    $('.flockr-action-table tbody tr').click(function () {
        var url = '/' + $(this).data('module') + '/' + $(this).data('controller') + '/' + $(this).data('action') + '?' + $(this).data('identifier') + '=' + $(this).data('id');
        var arguments = $(this).data('arguments')
        if (arguments == '') arguments = {};
        for(var key in arguments) {
            url += '&' + key + '=' + arguments[key];
        }
        window.location.href = url;
    });

});
