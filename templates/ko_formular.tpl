{foreach item=hid from=$tpl_hidden_inputs}
    <input type="hidden" name="{$hid.name}" value="{$hid.value}"/>
{/foreach}
{if $tpl_id}
    <input type="hidden" name="id" value="{$tpl_id}"/>
{/if}
<div class="row page-header">
    <div class="col-md-11">
        <h1>{$tpl_titel}</h1>
    </div>
    <div class="col-md-1 pull-right">
        {if $help.show}{$help.link}{/if}
    </div>
</div>

<div class="subpart">

    <!-- Formular-Daten -->

    <ul class="nav nav-tabs subpart-nav">
        {foreach key=id name=groups item=group from=$tpl_groups}
            {if $group.titel}
                <li><a data-toggle="tab" href="#frmgrp_{$id}">{$group.titel}</a></li>{/if}
        {/foreach}
    </ul>

    <div class="tab-content">
        {foreach key=id name=groups item=group from=$tpl_groups}
            <div id="frmgrp_{$id}" class="tab-pane fade">
                {if $group.forAll}
                    <div class="checkbox">
                    <label><input type="checkbox" name="koi[{$group.table}][doForAll]" checked /> Die folgenden Änderungen auf alle ausgewählten Datensätze anwenden: </label>
                    </div>
                    <hr />
                {/if}
                {foreach name=rows item=row from=$group.row}

                    {foreach name=inputs item=input from=$row.inputs}
                        <!-- TYPE: {$input.type} -->
                        {if $input.half == 1}
                            <div class="row">
                        {/if}
                        {if $input.half}
                            <div class="col-md-6">
                        {/if}
                            {include file="$ko_path/templates/ko_formular_elements.tpl"}
                        {if $input.half}
                            </div>
                        {/if}
                        {if $input.half == 2}
                            </div>
                        {/if}
                    {/foreach}

                {/foreach}
            </div>
        {/foreach}
    </div>
</div>
<hr/>
{if $tpl_special_submit}
    {$tpl_special_submit}
{else}
    <button type="submit" name="submit" class="btn btn-primary ko_form_submit {$submit_class}"
            value="{$tpl_submit_value}"
            onclick="{$tpl_onclick_action}set_action('{$tpl_action}', this)">
        <span class="glyphicon glyphicon-save"></span> {$tpl_submit_value}
    </button>
{/if}
{if $tpl_submit_as_new && !$force_hide_submit_as_new}
    <button type="submit" name="submit_as_new" value="{$tpl_submit_as_new}" class="btn btn-success"
            onclick="set_action('{$tpl_action_as_new}', this);">
        <span class="glyphicon glyphicon-plus"></span> {$tpl_submit_as_new}</button>
{/if}
{if !$tpl_hide_cancel}
    <button type="submit" name="cancel" value="{$label_cancel}" class="btn btn-default"
            onclick="set_action('{$tpl_cancel}', this);">
        <span class="glyphicon glyphicon-remove"></span> {$label_cancel}</button>
{/if}

{if $tpl_legend}
    <div style="margin-top: 10px; color: #666;">
        <img src="{$ko_path}images/{$tpl_legend_icon}" alt="legend" border="0" align="left"/>&nbsp;
        {$tpl_legend}
    </div>
{/if}

</td>
</tr>
</div>