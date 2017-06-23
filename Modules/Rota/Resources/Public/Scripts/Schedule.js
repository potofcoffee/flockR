/**
 * Created by chris on 09.06.2017.
 */

function reloadWholeSchedule(data, route) {
    route = route || 'rota/ajax/plan';
    $.ajax({
        url: baseUrl+route,
        data: data,
        success: function(data){
            $(ajaxArea).html(data);
            onScheduleLoaded();
        }
    });
}

function onScheduleLoaded() {
    $('.schedule-block-unlocked').on('click', function(){
        //alert($(this).find('.schedule-block-title').first().html());
        $('#modal-event-schedule-header').html(
            '<small>'+$(this).data('eventtitle')+'</small><br >' + $(this).find('.schedule-block-title').first().html()
        );
        // attach id data to submit button
        $('#modal-event-schedule-submit').data('event', $(this).data('event'));
        $('#modal-event-schedule-submit').data('team', $(this).data('team'));
        // get ajax form
        $.ajax({
                url: baseUrl+ 'rota/ajax/eventSchedule',
                data: {
                    event: $(this).data('event'),
                    team: $(this).data('team'),
                },
                success: function(data) {
                    $('#modal-event-schedule-content').html(data);
                    $('#modal-event-schedule').modal('show');
                }
        }
        );
    });

    // submit button:
    $('#modal-event-schedule-submit').on('click', function(){
        $('#modal-event-schedule').modal('hide');
        $.ajax({
            url: baseUrl+'rota/ajax/saveEventSchedule',
            data: {
                event: $(this).data('event'),
                team: $(this).data('team'),
                textEntries: $('textarea[name="textEntries"]').val(),
                people: $('input[name="scheduledPeople"]').val(),
            },
            success: function(data) {
                $(ajaxArea).html(data);
                onScheduleLoaded();
            }
        });
    });

    // change timespan:
    $('#select-timespan').on('change', function(){
        reloadWholeSchedule({
            rota_timespan: $(this).val()
        });
    })

    // move backwards
    $('#btn-timestart-back').on('click', function(){
        reloadWholeSchedule({
            changeTime: '-'+$('#select-timespan').val(),
        });
    });

    $('#btn-timestart-forward').on('click', function(){
        reloadWholeSchedule({
            changeTime: '+'+$('#select-timespan').val(),
        });
    });

    $('#btn-timestart-today').on('click', function(){
        reloadWholeSchedule({
            changeTime: $(this).data('date'),
        });
    });

    // lock/unlock buttons
    $('.btn-lock-event-schedule').on('click', function(){
        reloadWholeSchedule({
            event: $(this).data('event')
        }, 'rota/ajax/lockEvent');
    })

    $('.btn-unlock-event-schedule').on('click', function(){
        reloadWholeSchedule({
            event: $(this).data('event')
        }, 'rota/ajax/unlockEvent');
    })

    $('#btn-lock-all').on('click', function(){
        reloadWholeSchedule({}, 'rota/ajax/lockAll');
    });

    $('#btn-unlock-all').on('click', function(){
        reloadWholeSchedule({}, 'rota/ajax/unlockAll');
    });


}


$(document).ready(function(){
    $(document).on({
        ajaxStart: function() { $('body').addClass("loading");    },
        ajaxStop: function() { $('body').removeClass("loading"); }
    });

    // toggle teams
    $('.rota-team-toggle').on('change', function() {
        reloadWholeSchedule({
            team: $(this).data('team'),
            on: $(this).prop('checked')
        }, 'rota/ajax/toggleTeam');
    });

    $('.rota-eventgroup-toggle').on('change', function() {
        reloadWholeSchedule({
            eventgroup: $(this).data('eventgroup'),
            on: $(this).prop('checked')
        }, 'rota/ajax/toggleEventGroup');
    });

});
