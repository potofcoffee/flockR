{if $input.type == "text"}
    <div class="form-group">
        <label for="{$input.name}">{$input.desc}</label>
        <input class="form-control" type="text" name="{$input.name}" value="{$input.value}" {$input.params} />
    </div>
{elseif $input.type == "jsdate"}
    <div class="form-group">
        <label for="{$input.name}">{$input.desc}</label>
        <div class="input-group date">
            <span class="input-group-addon"><i
                        class="glyphicon glyphicon-th"></i></span>
            <input class="form-control" type="text" name="{$input.name}" value="{$input.value}" {$input.params} />
        </div>
    </div>
{elseif $input.type == "password"}
    <div class="form-group">
        <label for="{$input.name}">{$input.desc}</label>
        <input class="form-control" type="password" name="{$input.name}" value="{$input.value}" {$input.params} />
    </div>
{elseif $input.type == "text_mylist"}
    <div class="form-group">

        <label for="{$input.name}">{$input.desc}</label>
        <div class="input-group">
        <span class="input-group-addon">
    <img src="{$ko_path}images/icon_import_my_list.png" alt="import_my_list" title="{$label_text_mylist_import}"
         border="0" onclick="javascript:document.getElementsByName('{$input.name}')[0].value += ',{$input.mylist}';"/>
        </span>
            <input type="text" name="{$input.name}" value="{$input.value}" {$input.params} />
        </div>
        &nbsp;
    </div>
{elseif $input.type == "textarea"}
    <div class="form-group">
        <label for="{$input.name}">{$input.desc}</label>
        <textarea class="form-control" name="{$input.name}" {$input.params}>{$input.value}</textarea>
    </div>
{elseif $input.type == "richtexteditor"}
    <div class="form-group">
        <label for="{$input.name}">{$input.desc}</label>
        <textarea name="{$input.name}"
                  class="form-control richtexteditor" {$input.params}>{$input.value}</textarea>
    </div>
{elseif $input.type == "file"}
    <div class="form-group">
        <label for="{$input.name}">{$input.desc}</label>
        <input class="form-control" type="file" name="{$input.name}" {$input.params} />
        {if $input.special_value}<br/>{$input.special_value}{/if}
        {if $input.value != '' && $input.value != ' '}
            <br/>
            <a href="/{$input.value}"target="_blank">{$input.value}</a>{/if}
        {if $input.value2}<br/><input type="checkbox" name="{$input.name2}" value="1" />{$input.value2}{/if}
    </div>
{elseif $input.type == "color"}
    <div class="form-group">
        <label for="{$input.name}">{$input.desc}</label>
        <div class="input-group">
        <span class="input-group-addon">
    <span onclick="cp_show(document.getElementsByName('{$input.name}')[0]);" style="cursor:pointer;" name="pick"
          id="pick" class="colorpicker">
		&nbsp;
		<img src="{$ko_path}images/icon_colorpicker.png" alt="{$label_color_choose}" title="{$label_color_choose}"
             border="0"/>
	</span>
            </span>
            <input class="form-control" type="text" name="{$input.name}" id="colorpicker"
                   value="{$input.value}" {$input.params}
                   style="float: left; background:#{$input.value};color:{$input.value|contrast};"/>
        </div>
    </div>
{elseif $input.type == "label"}
    <div class="form-group">
        <label>{$input.value}</label>
    </div>
{elseif $input.type == "checkbox"}
    <div class="checkbox">
        <label for="{$input.name}">
            <input type="checkbox" name="{$input.name}" value="{$input.value}" {$input.params} /> {$input.desc}
            {if $input.desc2}({$input.desc2}){/if}
        </label>
    </div>
{elseif $input.type == 'switch'}
    <input type="hidden" name="{$input.name}" id="{$input.name|replace:"[":"_"|replace:"]":"_"}"
           value="{$input.value}"/>{$input.desc2}
    <b>{$input.desc}</b>
    <div class="input_switch switch_state_{$input.value}"
         name="switch_{$input.name|replace:"[":"_"|replace:"]":"_"}">
        <label class="switch_state_label_0" {if $input.value == 1}style="display: none;"{/if}>
            {if $input.label_0}{$input.label_0}{else}{ll key="no"}{/if}
        </label>
        <label class="switch_state_label_1" {if $input.value == 0}style="display: none;"{/if}>
            {if $input.label_1}{$input.label_1}{else}{ll key="yes"}{/if}
        </label>
    </div>
{elseif $input.type == "radio"}
    {html_radios name=$input.name values=$input.values output=$input.descs selected=$input.value separator=$input.separator escape=false}

{elseif $input.type == "select"}
    {if $input.buttons}
        <div class="row">
        <div class="col-md-9">
    {/if}
    <div class="form-group">
        <label for="{$input.name}">{$input.desc}</label>
        <select class="form-control"
                name="{$input.name}" {$input.params} {if $input.js_func_add}onclick="{$input.js_func_add}(this.options[parseInt(this.selectedIndex)].text, this.options[parseInt(this.selectedIndex)].value, this.name);"{/if}>
            {foreach from=$input.values item=v key=k}
                <option value="{$v}"
                        {if $v == $input.value}selected="selected"{/if} {if $v === '_DISABLED_'}disabled="disabled"{/if}>
                    {if $input.descs.$k}{$input.descs.$k}{else}{$input.descs.$v}{/if}
                </option>
            {/foreach}
        </select>
    </div>
    {if $input.buttons}
        </div>
        <div class="col-md-3">
            {$input.buttons}
        </div>
        </div>
    {/if}
{elseif $input.type == "multidateselect"}
    <input type="hidden" name="{$input.name}" value="{$input.avalue}"/>
    <input type="hidden" name="old_{$input.name}" value="{$input.avalue}"/>
    <div class="row">
        <div class="col-md-5">
            <div style="font-size:x-small;">{$label_form_ds_objects}:</div>
            {$input.dateselect}
        </div>
        <div class="col-md-2">
            <div style="font-size:x-small;">&nbsp;</div>
            <img src="{$ko_path}images/ds_del.gif" alt="del" title="{$label_doubleselect_remove}" border="0"
                 onclick="double_select_move('{$input.name}', 'del');"/>
        </div>
        <div class="col-md-5">
            <div style="font-size:x-small;">{$label_form_ds_assigned}:</div>
            <select name="sel_ds2_{$input.name}" {$input.params}>
                {foreach from=$input.avalues item=v key=k}
                    <option value="{$v}">{$input.adescs.$k}</option>
                {/foreach}
            </select>
        </div>
    </div>
{elseif $input.type == "doubleselect"}
    <label for="{$input.name}">{$input.desc}</label>
    <div class="row">
        <div class="col-md-6">
            <div class="row">
                <div class="col-md-11">
                    <input type="hidden" name="{$input.name}" value="{$input.avalue}"/>
                    <input type="hidden" name="old_{$input.name}" value="{$input.avalue}"/>
                    <div style="font-size:x-small;">{$label_form_ds_objects}:</div>
                    <select class="form-control" id="doubleselect" name="sel_ds1_{$input.name}" {$input.params}
                            onclick="{$input.js_func_add}(this.options[parseInt(this.selectedIndex)].text, this.options[parseInt(this.selectedIndex)].value, 'sel_ds2_{$input.name}', '{$input.name}');"
                            onkeydown="if(event.keyCode == 13 || event.which == 13) {literal}{{/literal}{$input.js_func_add}(this.options[parseInt(this.selectedIndex)].text, this.options[parseInt(this.selectedIndex)].value, 'sel_ds2_{$input.name}', '{$input.name}');{literal}}{/literal}">
                        {foreach from=$input.values item=v key=k}
                            <option value="{$v}">{$input.descs.$k}</option>
                        {/foreach}
                    </select>
                </div>
                <div class="col-md-1">
                    <div style="font-size:x-small;">&nbsp;</div>
                    {if $input.show_moves}
                        <img src="{$ko_path}images/ds_top.gif" border="0" alt="top" title="{$label_form_ds_top}"
                             onclick="double_select_move('{$input.name}', 'top');"/>
                        <br/>
                        <img src="{$ko_path}images/ds_up.gif" border="0" alt="up" title="{$label_form_ds_up}"
                             onclick="double_select_move('{$input.name}', 'up');"/>
                        <br/>
                        <img src="{$ko_path}images/ds_down.gif" border="0" alt="down" title="{$label_form_ds_down}"
                             onclick="double_select_move('{$input.name}', 'down');"/>
                        <br/>
                        <img src="{$ko_path}images/ds_bottom.gif" border="0" alt="bottom"
                             title="{$label_form_ds_bottom}"
                             onclick="double_select_move('{$input.name}', 'bottom');"/>
                        <br/>
                        <img src="{$ko_path}images/ds_del.gif" border="0" alt="del" title="{$label_form_ds_del}"
                             onclick="double_select_move('{$input.name}', 'del');"/>
                    {else}
                        <img src="{$ko_path}images/ds_del.gif" alt="del" title="{$label_doubleselect_remove}" border="0"
                             onclick="double_select_move('{$input.name}', 'del');"/>
                    {/if}


                </div>
            </div>

        </div>
        <div class="col-md-6">
            <div style="font-size:x-small;">{$label_form_ds_assigned}:</div>
            <select class="form-control" name="sel_ds2_{$input.name}" {$input.params}>
                {foreach from=$input.avalues item=v key=k}
                    <option value="{$v}">{$input.adescs.$k}</option>
                {/foreach}
            </select>

        </div>
    </div>
{elseif $input.type == "checkboxes"}
    <div class="form-group">
        <label>{$input.desc}</label>
        {assign var="height" value=$input.size*20}
        <div class="koi-checkboxes-container" {$input.params} style="height: {$height}px;">
            <input type="hidden" name="{$input.name}" value="{$input.avalue}" class="koi-checkboxes-value"/>
            <input type="hidden" name="old_{$input.name}" value="{$input.avalue}"/>
            {foreach from=$input.values item=v key=k}
                {if in_array($v,$input.avalues)}
                    {assign var="checked" value='checked="checked"'}
                    {assign var="class" value="koi-checkboxes-checked"}
                {else}
                    {assign var="checked" value=''}
                    {assign var="class" value=""}
                {/if}
                <div class="checkbox koi-checkboxes-entry {$class}">
                    <label>
                        <input type="checkbox" name="chk_{$input.name}" value="{$v}" {$checked} />
                        {$input.descs.$k}</label>
                </div>
            {/foreach}
        </div>
    </div>
{elseif $input.type == "textmultiplus"}
    <table>
        <tr>
            <td>
                <input type="hidden" name="{$input.name}" value="{$input.avalue}"/>
                <input type="hidden" name="old_{$input.name}" value="{$input.avalue}"/>
                <div style="font-size:x-small;">{$label_form_ds_objects}:</div>
                {assign var="size0" value=$input.size-2}
                <select name="sel_ds1_{$input.name}" size="{$size0}" {$input.params}
                        onclick="{$input.js_func_add}(this.options[parseInt(this.selectedIndex)].text, this.options[parseInt(this.selectedIndex)].value, 'sel_ds2_{$input.name}', '{$input.name}');"
                        onkeydown="if(event.keyCode == 13 || event.which == 13) {literal}{{/literal}{$input.js_func_add}(this.options[parseInt(this.selectedIndex)].text, this.options[parseInt(this.selectedIndex)].value, 'sel_ds2_{$input.name}', '{$input.name}');{literal}}{/literal}">
                    {foreach from=$input.values item=v key=k}
                        <option value="{$v}">{$input.descs.$k}</option>
                    {/foreach}
                </select>
                <br/>
                <input type="text" name="new_{$input.name}" class="textmultiplus_new"
                       placeholder="{ll key='textmultiplus_placeholder'}"/>
            </td>
            <td valign="top">
                <div style="font-size:x-small;">&nbsp;</div>
                {if $input.show_moves}
                    <img src="{$ko_path}images/ds_top.gif" border="0" alt="top" title="{$label_form_ds_top}"
                         onclick="double_select_move('{$input.name}', 'top');"/>
                    <br/>
                    <img src="{$ko_path}images/ds_up.gif" border="0" alt="up" title="{$label_form_ds_up}"
                         onclick="double_select_move('{$input.name}', 'up');"/>
                    <br/>
                    <img src="{$ko_path}images/ds_down.gif" border="0" alt="down" title="{$label_form_ds_down}"
                         onclick="double_select_move('{$input.name}', 'down');"/>
                    <br/>
                    <img src="{$ko_path}images/ds_bottom.gif" border="0" alt="bottom"
                         title="{$label_form_ds_bottom}"
                         onclick="double_select_move('{$input.name}', 'bottom');"/>
                    <br/>
                    <img src="{$ko_path}images/ds_del.gif" border="0" alt="del" title="{$label_form_ds_del}"
                         onclick="double_select_move('{$input.name}', 'del');"/>
                {else}
                    <img src="{$ko_path}images/ds_del.gif" alt="del" title="{$label_doubleselect_remove}" border="0"
                         onclick="double_select_move('{$input.name}', 'del');"/>
                {/if}
            </td>
            <td>
                <div style="font-size:x-small;">{$label_form_ds_assigned}:</div>
                <select name="sel_ds2_{$input.name}" {$input.params} size="{$input.size}">
                    {foreach from=$input.avalues item=v key=k}
                        <option value="{$v}">{$input.adescs.$k}</option>
                    {/foreach}
                </select>
            </td>
        </tr>
    </table>
{elseif $input.type == "dyndoubleselect"}
    <table>
        <tr>
            <td>
                <input type="hidden" name="{$input.name}" value="{$input.avalue}"/>
                <input type="hidden" name="old_{$input.name}" value="{$input.avalue}"/>
                <select name="sel_ds1_{$input.name}" {$input.params} size="10"
                        onclick="{if !$input.nochecklist}if(!checkList(1)) return false;{/if}double_select_add(this.options[parseInt(this.selectedIndex)].text, this.options[parseInt(this.selectedIndex)].value, 'sel_ds2_{$input.name}', '{$input.name}');">
                </select>
            </td>
            <td valign="top">
                <img src="{$ko_path}images/ds_del.gif" alt="del" title="{$label_doubleselect_remove}" border="0"
                     onclick="double_select_move('{$input.name}', 'del');"/>
            </td>
            <td>
                <select name="sel_ds2_{$input.name}" {$input.params} size="10">
                    {foreach from=$input.avalues item=v key=k}
                        <option value="{$v}">{$input.adescs.$k}</option>
                    {/foreach}
                </select>
            </td>
        </tr>
    </table>
{elseif $input.type == "peoplesearch"}
    <table>
        <tr>
            <td valign="top">
                <div class="peoplesearchwrap">
                    <input type="text" class="peoplesearch" name="txt_{$input.name}" autocomplete="off"
                           placeholder="{ll key='peoplesearch_placeholder'}"/><br/>
                    <select class="peoplesearchresult"
                            size="{if $input.sizeresults}{$input.sizeresults}{else}4{/if}"
                            name="sel_ds1_{$input.name}">
                </div>
            </td>
            <td valign="top">
                <img src="{$ko_path}images/ds_del.gif" alt="del" title="{$label_doubleselect_remove}" border="0"
                     onclick="double_select_move('{$input.name}', 'del');"/>
            </td>
            <td valign="top">
                <input type="hidden" name="{$input.name}" value="{$input.avalue}"/>
                <input type="hidden" name="old_{$input.name}" value="{$input.avalue}"/>
                <select name="sel_ds2_{$input.name}" {$input.params}
                        size="{if $input.sizeact}{$input.sizeact}{else}7{/if}" class="peoplesearchact">
                    {foreach from=$input.avalues item=v key=k}
                        {if $v}
                            <option value="{$v}">{$input.adescs.$k}</option>
                        {/if}
                    {/foreach}
                </select>
            </td>
        </tr>
    </table>
{elseif $input.type == "peoplefilter"}
    <table>
        <tr>
            <td valign="top">
                <input type="hidden" name="{$input.name}" value="{$input.avalue}" class="peoplefilter-value"/>
                <input type="hidden" name="old_{$input.name}" value="{$input.avalue}"/>
                <div style="font-size:x-small;">{ll key="form_peoplefilter_filter"}:</div>
                <select name="peoplefilter_type_{$input.name}" size="0" {$input.params} class="sel-peoplefilter">
                    <option value=""></option>
                    {foreach from=$input.filters item=v key=k}
                        <option value="{$k}">{$v}</option>
                    {/foreach}
                </select>
                <br/>
                <div id="peoplefilter_vars_{$input.name}" name="peoplefilter_vars_{$input.name}"></div>
                <input type="button" name="submit_peoplefilter_{$input.name}" value="{ll key='filter_add'}"
                       class="peoplefilter-submit"/>
            </td>
            <td valign="top">
                <div style="font-size:x-small;">&nbsp;</div>
                <img src="{$ko_path}images/ds_del.gif" alt="del" title="{$label_doubleselect_remove}" border="0"
                     onclick="double_select_move('{$input.name}', 'del');"/>
            </td>
            <td valign="top">
                <div style="font-size:x-small;">{$label_form_ds_assigned}:</div>
                <select name="sel_ds2_{$input.name}" {$input.params} size="{$input.size}" class="peoplefilter-act">
                    {foreach from=$input.avalues item=v key=k}
                        <option value="{$v}">{$input.adescs.$k}</option>
                    {/foreach}
                </select>
            </td>
        </tr>
    </table>
{elseif $input.type == "foreign_table"}
    <span class="form_ft_new" data-field="{$ft_field}" data-after="0" data-pid="{$ft_pid}">
	<img src="{$ko_path}images/icon_plus.png" border="0"/>
        {$label_form_ft_new}
	</span>
    {if $ft_preset_table != null}
        <span style="margin-left:20px;">
			<script>
				<!--
                if (window.kota_ft_alert_no_join_value === undefined) {ldelim}
                    window.kota_ft_alert_no_join_value = {ldelim}{rdelim};
                    {rdelim}
                window.kota_ft_alert_no_join_value['{$ft_field}'] = '{$ft_alert_no_join_value}';
                //-->
			</script>
			<button class="form_ft_load_preset" data-field="{$ft_field}" data-after="0" data-pid="{$ft_pid}"
                    data-preset-table="{$ft_preset_table}" data-preset-join-value-local="{$ft_preset_join_value_local}"
                    data-preset-join-column-foreign="{$ft_preset_join_column_foreign}">
				{ll key="form_ft_button_load_presets"}
			</button>
		</span>
    {/if}
    <div id="ft_content_{$ft_field}" name="ft_content_{$ft_field}">{$ft_content}</div>
{elseif $input.type == "html"}
    {$input.value}

{elseif $input.type == "hidden"}
    <input type="hidden" name="{$input.name}" value="{$input.ovalue}"/>
    {$input.value}

{elseif $input.type == "   "}
    <br/>
{/if}
