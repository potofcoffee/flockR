<input type="hidden" value="{$tpl_id}" name="leute_id"/>
<input type="hidden" value="{$tpl_nl_id}" name="add_to_nl"/>
<input type="hidden" value="{$tpl_nl_mod_id}" name="nl_mod_id"/>
<input type="hidden" name="hid_new_family" value="0"/>

<div class="page-header">
    <h1>{$tpl_titel}</h1>
</div>

<!-- tabs -->
<ul class="nav nav-tabs subpart-nav">
    {foreach from=$tpl_rows item=row}
        {foreach from=$row.inputs item=input}
            {if $input.type == "header"}
                <li><a data-toggle="tab" href="#frmgrp_{$input.id}">{$input.value}</a></li>
            {/if}
        {/foreach}
    {/foreach}
    {if !$hide_fam}
        <li><a data-toggle="tab" href="#frmgrp_family">{$label_family}</a></li>{/if}
</ul>
<div class="subpart">
    <div class="tab-content">
        <div>
            {foreach from=$tpl_rows item=row}
            {foreach from=$row.inputs item=input}


            {if $input.type == "varchar" || $input.type == "date" || $input.type == "smallint" || $input.type == "mediumint"}
            <div class="form-group">
                <label for="{$input.name}">
                    {if $input.fam_feld}<img src="{$ko_path}images/icon_familie.png"
                                             alt="{$label_family|truncate:1:""}"
                                             title="{$label_family}" />{/if}
                    {$input.desc}{if $input.fam_feld_warn_changes}   <span class="family_field_warning"
                                                                           style="color:orangered;visibility:hidden">{ll key="leute_warning_family_fields"}</span>{/if}
                </label>
                {if $input.chk_preferred}
                <div class="input-group" style="width: 100%"><span
                            class="input-group-addon">{$input.chk_preferred}</span>{/if}
                    <input class="form-control" type="text" name="{$input.name}" value="{$input.value}" {$input.params}
                           size="40"/>
                    {if $input.chk_preferred}</div>{/if}
            </div>
            {elseif $input.type == "tinyint"}
            <div class="checkbox">
                <label>
                    <input type="checkbox" name="{$input.name}" value="1" {$input.params} />
                    {$input.desc}
                </label>
            </div>
            {elseif $input.type == "blob" || $input.type == "text"}
            <div class="form-group">
                <label for="{$input.name}">
                    {if $input.fam_feld}<img src="{$ko_path}images/icon_familie.png"
                                             alt="{$label_family|truncate:1:""}"
                                             title="{$label_family}" />{/if}
                    {$input.desc}{if $input.fam_feld_warn_changes}   <span class="family_field_warning"
                                                                           style="color:orangered;visibility:hidden">{ll key="leute_warning_family_fields"}</span>{/if}
                </label>
                <textarea class="form-control" name="{$input.name}" {$input.params} cols="40" rows="3">
                {$input.value}
            </textarea>
            </div>
            {elseif $input.type == "enum"}
            <div class="form-group">
                <label for="{$input.name}">
                    {if $input.fam_feld}<img src="{$ko_path}images/icon_familie.png"
                                             alt="{$label_family|truncate:1:""}"
                                             title="{$label_family}" />{/if}
                    {$input.desc}{if $input.fam_feld_warn_changes}   <span class="family_field_warning"
                                                                           style="color:orangered;visibility:hidden">{ll key="leute_warning_family_fields"}</span>{/if}
                </label>
                <select class="form-control" name="{$input.name}" {$input.params}>
                    {foreach key=k item=v from=$input.values}
                        <option value="{$v}"
                                {if $v == $input.value}selected="selected"{/if}>{$input.descs[$k]}</option>
                    {/foreach}
                </select>
            </div>
            {elseif $input.type == "doubleselect"}
            <td class="formular_content" {$input.colspan}>
                <table>
                    <tr>
                        <td>
                            <input type="hidden" name="{$input.name}" value="{$input.avalue}"/>
                            <input type="hidden" name="old_{$input.name}" value="{$input.avalue}"/>
                            <div style="font-size:x-small;">{$label_form_ds_objects}:</div>
                            <select name="sel_ds1_{$input.name}" {$input.params} size="6"
                                    onclick="double_select_add(this.options[parseInt(this.selectedIndex)].text, this.options[parseInt(this.selectedIndex)].value, 'sel_ds2_{$input.name}', '{$input.name}');">
                                {foreach from=$input.values item=v key=k}
                                    <option value="{$v}">{$input.descs.$k}</option>
                                {/foreach}
                            </select>
                        </td>
                        <td valign="top">
                            <div style="font-size:x-small;">&nbsp;</div>
                            {if $input.show_moves}
                                <img src="{$ko_path}images/ds_top.gif" border="0" alt="top"
                                     title="{$label_form_ds_top}"
                                     onclick="double_select_move('{$input.name}', 'top');"/>
                                <br/>
                                <img src="{$ko_path}images/ds_up.gif" border="0" alt="up"
                                     title="{$label_form_ds_up}"
                                     onclick="double_select_move('{$input.name}', 'up');"/>
                                <br/>
                                <img src="{$ko_path}images/ds_down.gif" border="0" alt="down"
                                     title="{$label_form_ds_down}"
                                     onclick="double_select_move('{$input.name}', 'down');"/>
                                <br/>
                                <img src="{$ko_path}images/ds_bottom.gif" border="0" alt="bottom"
                                     title="{$label_form_ds_bottom}"
                                     onclick="double_select_move('{$input.name}', 'bottom');"/>
                                <br/>
                            {/if}
                            <img src="{$ko_path}images/ds_del.gif" alt="x"
                                 title="{$label_doubleselect_remove}" border="0"
                                 onclick="double_select_move('{$input.name}', 'del');"/>
                        </td>
                        <td>
                            <div style="font-size:x-small;">{$label_form_ds_assigned}:</div>
                            <select name="sel_ds2_{$input.name}" {$input.params} size="6">
                                {foreach from=$input.avalues item=v key=k}
                                    <option value="{$v}">{$input.adescs.$k}</option>
                                {/foreach}
                            </select>
                        </td>
                    </tr>
                </table>
            </td>
            {elseif $input.type == "groupselect"}
            <td class="formular_content" {$input.colspan}>
                <table>
                    <tr>
                        <td>
                            <input type="hidden" name="{$input.name}" value="{$input.avalue}"/>
                            <input type="hidden" name="old_{$input.name}" value="{$input.avalue}"/>
                            <div style="font-size:x-small;">{$label_group}:</div>
                            <select name="sel_ds0_{$input.name}" {$input.params} size="10"
                                    onclick="if(!checkList(1)) return false;fill_grouproles_select(this.options[parseInt(this.selectedIndex)].value);">
                            </select>
                        </td>
                        <td>
                            <div style="font-size:x-small;">{$label_grouprole}:</div>
                            <select name="sel_ds1_{$input.name}" {$input.params} size="10"
                                    onclick="double_select_add(this.options[parseInt(this.selectedIndex)].text, this.options[parseInt(this.selectedIndex)].value, 'sel_ds2_{$input.name}', '{$input.name}');{$input.onclick_2_add}">
                            </select>
                        </td>
                        <td valign="top">
                            <br/>
                            <img src="{$ko_path}images/ds_del.gif" alt="x"
                                 title="{$label_doubleselect_remove}" border="0"
                                 onclick="{if $allow_assign}double_select_move('{$input.name}', 'del');{$input.onclick_del_add}{/if}"/>
                        </td>
                        <td>
                            <div style="font-size:x-small;">{$label_group_assigned}:</div>
                            <select name="sel_ds2_{$input.name}" {$input.params} size="10">
                                {foreach from=$input.avalues item=v key=k}
                                    <option value="{$v}"
                                            title="{$input.adescs.$k}">{$input.adescs.$k}</option>
                                {/foreach}
                            </select>
                        </td>
                    </tr>
                </table>
            </td>
            {elseif $input.type == "file"}
            <td class="formular_content" {$input.colspan}>
                <input type="file" name="{$input.name}" {$input.params} />
                {if $input.value}<br/>{$input.value}{/if}
                {if $input.value}
                    <br/>
                    <input type="checkbox" name="{$input.name2}"value="1" />{$input.value2}{/if}
            </td>
            {elseif $input.type == "_save"}
            <td colspan="2" align="center">
                <input type="submit" name="submit" value="{$label_save}"
                       onclick="set_action('{$tpl_action}', this)"/>
                &nbsp;&nbsp;&nbsp;
                <input type="submit" name="cancel" value="{$label_cancel}"
                       onclick="set_action('show_all', this)"/>
            </td>
            {elseif $input.type == "html"}
            <td class="formular_content" {$input.colspan}>
                {$input.value}
            </td>
            {elseif $input.type == "header"}
        </div>
        <div id="frmgrp_{$input.id}" class="tab-pane fade">
            {elseif $input.type == "   "}
            <td colspan="2"><br/></td>
            {else}
            <td class="formular_content" {$input.colspan}>
                {include file="$ko_path/templates/ko_formular_elements.tpl"}
            </td>
            {/if}


            {/foreach}
            {/foreach}
        </div>
        {if !$hide_fam}
            <div id="frmgrp_family" class="tab-pane fade">
                {if $fam}
                    <div class="row">
                        <div class="col-md-11">
                            <div class="form-group">
                                <select class="form-control" name="sel_familie" size="0" {$fam_params}>
                                    {foreach from=$fam_sel.values item=v key=k}
                                        <option value="{$v}"
                                                {if $v == $fam_sel.sel}selected="selected"{/if}>{$fam_sel.descs.$k}</option>
                                    {/foreach}
                                </select>
                            </div>
                        </div>
                        <div class="col-md-1 pull-right">
                            <div class="form-group">
                                <input class="btn btn-success" type="button" name="submit_neue_fam"
                                       value="{$label_family_new}"
                                       onclick="set_hidden_value('hid_new_family', '1', this);set_vis('fam_content');" {$fam_params} />
                                &nbsp;&nbsp;&nbsp;&nbsp;</div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="input_famfunction">&nbsp;{$label_familyrole}</label>
                        <select class="form-control" name="input_famfunction" size="0" {$fam_params}>
                            {foreach from=$famfunction.values item=v key=k}
                                <option value="{$v}"
                                        {if $v == $famfunction.sel}selected="selected"{/if}>{$famfunction.descs.$k}</option>
                            {/foreach}
                        </select>
                    </div>
                    {foreach item=fd from=$cols_familie}
                        <div class="form-group">
                            <label for="{$fd.name}">{$fd.desc}</label>
                            {if $fd.type == "text"}
                                <input class="form-control" type="text" name="{$fd.name}" value="{$fd.value}"
                                       size="40" {$fam_params} />
                            {elseif $fd.type == "select"}
                                <select class="form-control" name="{$fd.name}" size="0" {$fam_params}>
                                    {foreach from=$fd.values item=v key=k}
                                        <option value="{$v}"
                                                {if $v == $fd.value}selected="selected"{/if}>{$fd.descs.$k}</option>
                                    {/foreach}
                                </select>
                            {/if}
                        </div>
                    {/foreach}
                    &nbsp;&nbsp;
                    <input class="btn btn-primary" type="submit" name="submit_fam" value="{$label_ok}"
                           onclick="set_action('{$tpl_action}', this)" {$fam_params} />
                {else}
                    <div class="row">
                        <div class="col-md-11">
                            <div class="form-group">
                                <select class="form-control" name="sel_familie" size="0" {$fam_params}>
                                    {foreach from=$fam_sel.values item=v key=k}
                                        <option value="{$v}"
                                                {if $v == $fam_sel.sel}selected="selected"{/if}>{$fam_sel.descs.$k}</option>
                                    {/foreach}
                                </select>
                            </div>
                        </div>
                        <div class="col-md-1 pull-right">
                            <div class="form-group">
                                <input class="btn btn-success" type="button" name="submit_neue_fam"
                                       value="{$label_family_new}"
                                       onclick="set_hidden_value('hid_new_family', '1', this);set_vis('fam_content');" {$fam_params} />
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="input_famfunction">{$label_familyrole}</label>
                        <select class="form-control" name="input_famfunction" size="0" {$fam_params}>
                            {foreach from=$famfunction.values item=v key=k}
                                <option value="{$v}"
                                        {if $v == $famfunction.sel}selected="selected"{/if}>{$famfunction.descs.$k}</option>
                            {/foreach}
                        </select>
                    </div>
                    &nbsp;&nbsp;
                    {foreach item=fd from=$cols_familie}
                        <div class="form-group">
                            <label for="{$fd.name}">{$fd.desc}</label>
                            {if $fd.type == "text"}
                                <input class="form-control" type="text" name="{$fd.name}" value="{$fd.value}"
                                       size="40" {$fam_params} />
                            {elseif $fd.type == "select"}
                                <select class="form-control" name="{$fd.name}" size="0" {$fam_params}>
                                    {foreach from=$fd.values item=v key=k}
                                        <option value="{$v}"
                                                {if $v == $fd.value}selected="selected"{/if}>{$fd.descs.$k}</option>
                                    {/foreach}
                                </select>
                            {/if}
                        </div>
                    {/foreach}
                    <input class="btnbtn-primary" type="submit" name="submit_fam" value="{$label_ok}"
                           onclick="set_action('{$tpl_action}', this)" {$fam_params} />
                    &nbsp;&nbsp;
                {/if}
            </div>
        {/if}
        <!-- family data end -->

    </div>
</div>
<hr/>

{if $tpl_legend}
    <div style="margin-top: 10px; color: #666;">
        <img src="{$ko_path}images/{$tpl_legend_icon}" alt="legend" border="0" align="left"/>&nbsp;
        {$tpl_legend}
    </div>
    <hr />
{/if}

<div class="row">
    <div class="col-md-6">
        <input class="btn btn-primary" type="submit" name="submit" value="{$label_save}"
               onclick="set_action('{$tpl_action}', this)"/>&nbsp;&nbsp;&nbsp;
        <input class="btn btn-default" type="submit" name="cancel" value="{$label_cancel}"
               onclick="set_action('show_all', this)"/>
        {if $tpl_action_neu != ""}
            <input class="btn btn-success" type="submit" name="submit_neu" value="{$label_as_new_person}"
                   onclick="set_action('{$tpl_action_neu}', this)"/>
        {/if}
    </div>
    <div class="col-md-6">
        {if $announce_values}
            <div class="form-group">
                <label for="sel_announce_changes">{$label_announce_description}</label>
                <select class="form-control" name="sel_announce_changes[]" multiple="multiple" size="4"
                        style="width: 200px;">
                    {foreach from=$announce_values item=v key=k}
                        <option value="{$v}">{$announce_descs.$k}</option>
                    {/foreach}
                </select>
            </div>
        {/if}
    </div>
</div>



