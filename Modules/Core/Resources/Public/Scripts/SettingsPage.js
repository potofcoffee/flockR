
$(document).ready(function(){

    //$('.richtexteditor').ckeditor({});

    $('.settings-group').hide();
    var url = window.location.href;
    if (url.indexOf("#") > 0) {
        var hash = url.substring(url.indexOf("#")+1);
        $('#group_'+hash).show();
        $('.sidebar-submenu li.active').removeClass('active');
        $('#accordion .panel-collapse.in').removeClass('in').removeClass('active');
        $('#menu_'+hash).parent().parent().parent().addClass('in active');
        $('#menu_'+hash).addClass('active');
    } else {
        $('.settings-group').first().show();
    }


    $('.sidebar-submenu li').on('click', 'a', function(event){
        $('.sidebar-submenu li.active').removeClass('active');
        $(this).parent().addClass('active');

        $('.settings-group').hide();
        $('#group_'+$(this).data('toggle')).show();
        //event.preventDefault();
    });
});
