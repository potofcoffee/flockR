/**
 * Created by chris on 23.03.2017.
 */



$(document).ready(function(){

    function getContainer(el) {
        return $(el).parent().parent().parent();
    }


    function updateNewElementAjaxForm() {
        $.ajax({
            url: '/agenda/item/form/'+$('#select-new-element-type').val(),
        }).done(function(data) {
            $('#new-element-ajax-form').html(data);
        });
    }

    updateNewElementAjaxForm();

    var group = $('#agenda-plan').sortable({
        handle: 'span.fa-arrows',
        nested: true,
        onDrop: function ($item, container, _super) {
            $('#debug').html('<pre>'+JSON.stringify(group.sortable("serialize").get(), null, "  ")+'</pre>');
            _super($item, container);
        },
        isValidTarget: function ($item, container) {
            console.debug($($item).data('type'));
            console.debug(container.el.nodeName);
            return true;
        }
    });




    $('#add-element-buttonDISABLED').on('click', function(){
        $('#agenda-plan').append('<li><span class="fa fa-arrows"></span> Neues Element<ul></ul></li>')
    });

    $('.delete-element-button').on('click', function(){
        getContainer(this).remove();
    });

    $('#select-new-element-type').on('change', updateNewElementAjaxForm);


    $('#add-this-new-element-button').on('click', function(){
        var itemData = new Object();
        $('.item-data').each(function(){
            itemData[$(this).attr('name')] = $(this).val();
        });

        $.ajax({
            url: '/agenda/item/plan/'+$('#select-new-element-type').val(),
            data: {item: itemData}
        }).done(function(data) {
            $('#agenda-plan').append(data);
        });
    })
});
