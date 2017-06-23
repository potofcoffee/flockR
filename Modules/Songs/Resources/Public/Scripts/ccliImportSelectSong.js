$(document).ready(function(){
    $('.get-songdata').each(function(){
        $(this).load('/songs/song/ccliSongData', {
            song: $(this).data('song')
        });
        $('[data-toggle="tooltip"]').tooltip();
    });
});

