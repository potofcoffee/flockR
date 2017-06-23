<div class="row">
    <div class="col-lg-12 col-md-12">
        <div class="panel panel-info">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3">
                        <i class="fa fa-birthday-cake fa-5x"></i>
                    </div>
                    <div class="col-xs-9 text-right">
                        <table width="100%" class="table">
                            {foreach from=$people item=l}
                                <tr>

                                    {if $tpl_fm_pos == 'm'}
                                        <td><span class="label {if $l.deadline <0}label-danger{elseif $l.deadline == 0}label-warning{else}label-success{/if}">{if $l.deadline > 0}+{/if}{$l.deadline}</span></td>
                                        <td><a href="{$l._link}">{$l.vorname} {$l.nachname}</a></td>
                                        <td>{$l.alter} {$label_years}</td>
                                        <td>{$l.geburtsdatum}</td>
                                    {else}
                                        <td>{$l.deadline}</td>
                                        <td><a href="{$l._link}"
                                               onmouseover="tooltip.show('{$l._tooltip}','','b','{$ttpos}')"
                                               onmouseout="tooltip.hide()">
                                                {$l.vorname} {$l.nachname}</a>
                                        </td>
                                    {/if}

                                </tr>
                            {/foreach}
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


