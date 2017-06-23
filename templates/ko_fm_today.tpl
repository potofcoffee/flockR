{if $show_daten_heute}
    <div class="row">
        <div class="col-lg-12 col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-xs-2">
                            <i class="fa fa-calendar fa-5x"></i>
                        </div>
                        <div class="col-xs-10">
                            <h2>{$title_event_today} ({$datum_heute})</h2>
                            <table class="table">
                                <tbody>
                                {foreach from=$today_daten_heute item=h}
                                    <tr>
                                        <td>
                                            {$h.startzeit}{if $h.endzeit != ""} - {$h.endzeit}{/if}
                                            {if $h.startzeit == $allday}{else} Uhr{/if}
                                        </td>
                                        <td>
                                            {$h.eventgruppe}:<br/>
                                            <b>{if $h.title != ""}{$h.title}{/if}</b>
                                        </td>
                                    </tr>
                                {/foreach}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
{/if}
{if $show_daten_woche}
    <div class="row">
        <div class="col-lg-12 col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-xs-2">
                            <i class="fa fa-calendar fa-5x"></i>
                        </div>
                        <div class="col-xs-10">
                            <h2>{$title_event_week}</h2>
                            <table class="table">
                                <tbody>
                                {foreach from=$today_daten_woche item=h}
                                    <tr>
                                        <td>
                                            {$h.wochentag}, {$h.startdatum|truncate:6:""}{if $h.enddatum != ""} - {$h.enddatum|truncate:6:""}{/if}
                                        </td>
                                        <td>
                                            {$h.startzeit}{if $h.endzeit != ""} - {$h.endzeit}{/if}
                                            {if $h.startzeit == $allday}{else} Uhr{/if}
                                        </td>
                                        <td>
                                            {$h.eventgruppe}:<br/>
                                            <b>{if $h.title != ""}{$h.title}{/if}</b>
                                        </td>
                                    </tr>
                                {/foreach}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
{/if}
{if $show_res}
    <div class="row">
        <div class="col-lg-12 col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-xs-2">
                            <i class="fa fa-bed fa-5x"></i>
                        </div>
                        <div class="col-xs-10">
                            <h2>{$title_res_week}</h2>
                            <table class="table">
                                <tbody>
                                {foreach from=$today_res_woche item=h}
                                    <tr>
                                        <td>
                                            {$h.wochentag}, {$h.startdatum|truncate:6:""}{if $h.enddatum != ""} - {$h.enddatum|truncate:6:""}{/if}
                                        </td>
                                        <td>
                                            {$h.startzeit}{if $h.endzeit != ""} - {$h.endzeit}{/if}
                                            {if $h.startzeit == $allday}{else} Uhr{/if}
                                        </td>
                                        <td>
                                            <b>{$h.item}</b>
                                            {if $h.zweck != ""}<br />{$h.zweck}{/if}
                                        </td>
                                    </tr>
                                {/foreach}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
{/if}
{if $show_res_mod}
    <div class="row">
        <div class="col-lg-12 col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-xs-2">
                            <i class="fa fa-bed fa-5x"></i>
                        </div>
                        <div class="col-xs-10">
                            <h2>{$title_res_new}</h2>
                            <table class="table">
                                <tbody>
                                {foreach from=$today_res_mod item=h}
                                    <tr>
                                        <td>
                                            {$h.wochentag}, {$h.startdatum|truncate:6:""}{if $h.enddatum != ""} - {$h.enddatum|truncate:6:""}{/if}
                                        </td>
                                        <td>
                                            {$h.startzeit}{if $h.endzeit != ""} - {$h.endzeit}{/if}
                                            {if $h.startzeit == $allday}{else} Uhr{/if}
                                        </td>
                                        <td>
                                            <b>{$h.item}</b>
                                            {if $h.zweck != ""}<br />{$h.zweck}{/if}
                                            <br />
                                            {if $h.name != ""}{$h.name}{/if}
                                            {if $h.email != "" || $h.telefon != ""}
                                                ({$h.email}, {$h.telefon})
                                            {/if}
                                        </td>
                                    </tr>
                                {/foreach}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
{/if}


{if $show_leute_change}
    <h2>{$title_people_new}:</h2>
    <table class="table" width="100%" cellspacing="0">
        <tr>
            <td class="news_content">
                {foreach from=$today_leute_change item=d}
                    <b>{if $d.link}<a href="{$d.link}">{/if}{$d.name}{if $d.link}</a>{/if}:</b>
                    &nbsp;
                    <i>({$d.user})</i>
                    &nbsp;{$d.log}
                    <br/>
                {/foreach}
            </td>
        </tr>
    </table>
{/if}

