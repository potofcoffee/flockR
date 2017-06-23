/*
 * FLOCKR
 * Multi-Purpose Church Administration Suite
 * http://github.com/potofcoffee/flockr
 * http://flockr.org
 *
 * Copyright (c) 2016+ Christoph Fischer (chris@toph.de)
 *
 * Parts copyright 2003-2015 Renzo Lauper, renzo@churchtool.org
 * FlockR is a fork from the kOOL project (www.churchtool.org). kOOL is available
 * under the terms of the GNU General Public License (see below).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
/*
<f:debug>{session}</f:debug>
*/
var sessionTimeout = {session.timeout};
var initialSessionTimeout = {session.timeout};
var sessionTimer = 0;


function sessionTimeDown(first) {
    var checkInterval  = 1;   //Checking intervall
    var warningTime    = 180;  //Time in seconds before the session timeout when the warning should be displayed
    var timeoutUrl     = '{FLOCKR_baseUrl}';
    //var autoLogout     = {session.autoLogout};
    var autoLogout     = 0;
    var showWarning    = {session.warning};

    if(!first) sessionTimeout -= checkInterval;

    if(sessionTimeout <= 0) {
        if(autoLogout) {
            $('#sessionTimeoutWarning').modal('hide');
            window.location.href = timeoutUrl;
        } else {
            $('#sessionTimeoutWarning').modal('hide');
            $('#sessionTimedOutReLoginForm').attr('action', window.location.href);
            $('#sessionTimeoutLoggedOut').modal('show');
            return;
        }
    } else if(sessionTimeout <= warningTime && showWarning) {
        $('#sessionTimeoutWarning').modal('show');
    }
    down = setTimeout("sessionTimeDown(false)", checkInterval*1000);
}

function updateSessionTimers() {
    sessionTimer++;
    $('.sessionTimeoutCounter').html(sessionTimer.toHHMMSS()+', '+sessionTimeout.toHHMMSS()+' verbleiben');
    var timers = setTimeout("updateSessionTimers()", 1000);
}

/**
 * Dummy to avoid errors
 */
function session_time_init() {

}

$(document).ready(function(){
    // init session timer
    if(sessionTimeout <= 0) return;
    sessionTimeDown(true);
    updateSessionTimers();

    $('#btnSessionTimeoutReset').on('click', function(){
        $('#sessionTimeoutWarning').modal('hide');
        sessionTimeout = initialSessionTimeout;
    });
});



Number.prototype.toHHMMSS = function () {
    var sec_num = parseInt(this, 10); // don't forget the second param
    var hours   = Math.floor(sec_num / 3600);
    var minutes = Math.floor((sec_num - (hours * 3600)) / 60);
    var seconds = sec_num - (hours * 3600) - (minutes * 60);

    if (hours   < 10) {hours   = "0"+hours;}
    if (minutes < 10) {minutes = "0"+minutes;}
    if (seconds < 10) {seconds = "0"+seconds;}
    if (hours > 0) {
        return hours+':'+minutes+':'+seconds;
    } else {
        return minutes+':'+seconds;

    }
}
