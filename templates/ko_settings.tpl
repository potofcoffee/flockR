<div class="page-header">
    <div class="row">
        <div class="col-sm-11">

            <h1>{$tpl_titel}</h1>
        </div>
        <div class="col-sm-1">
            {if $help.show}{$help.link}{/if}
        </div>
    </div>
</div>
<div class="subpart">
    <ul class="nav nav-tabs subpart-nav">
        {section name=part loop=$tpl_parts}
            {if $tpl_parts[part].titel}
                <li><a data-toggle="tab" href="#frmgrp_{$tpl_parts[part].id}">{$tpl_parts[part].titel} </a></li>{/if}
        {/section}
    </ul>
</div>
<div class="tab-content">
    {section name=part loop=$tpl_parts}
        <div id="frmgrp_{$tpl_parts[part].id}" class="tab-pane fade {if $tpl_parts[part].index==0 }in active{/if}">
            {foreach name=settings item=setting from=$tpl_parts[part].settings}
                {if $setting.type == "text"}
                    <div class="form-group">
                        <label for="{$setting.name}">{$setting.desc}</label>
                        <input class="form-control" type="text" name="{$setting.name}"
                               value="{$setting.value}" {$setting.params} />
                    </div>
                {elseif $setting.type == "password"}
                    <div class="form-group">
                        <label for="{$setting.name}">{$setting.desc}</label>
                        <input class="form-control" type="password" name="{$setting.name}"
                               value="{$setting.value}" {$setting.params} />
                    </div>
                {elseif $setting.type == "textarea"}
                    <div class="form-group">
                        <label for="{$setting.name}">{$setting.desc}</label>
                        <textarea class="form-control"
                                  name="{$setting.name}" {$setting.params}>{$setting.value}</textarea>
                    </div>
                {elseif $setting.type == "richtexteditor"}
                    <div class="form-group">
                        <label for="{$setting.name}">{$setting.desc}</label>
                        <textarea class="form-control richtexteditor"
                                  name="{$setting.name}" {$setting.params}>{$setting.value}</textarea>
                    </div>
                {elseif $setting.type == "checkbox"}
                    <div class="checkbox">
                        <label><input type="checkbox" name="{$setting.name}"
                                      value="1" {$setting.params} /> {$setting.desc}</label>
                    </div>
                {elseif $setting.type == 'switch'}
                    <div class="form-group">
                        <label>{$setting.desc}</label>
                        <input type="hidden" name="{$setting.name}" id="{$setting.name}"
                               value="{$setting.value}"/>{$setting.desc2}
                        <div class="input_switch switch_state_{$setting.value}"
                             name="switch_{$setting.name}">
                            <label class="switch_state_label_0"
                                   {if $setting.value == 1}style="display: none;"{/if}>{$setting.label_0}</label>
                            <label class="switch_state_label_1"
                                   {if $setting.value == 0}style="display: none;"{/if}>{$setting.label_1}</label>
                        </div>
                    </div>
                {elseif $setting.type == "select"}
                    <div class="form-group">
                        <label for="{$setting.name}">{$setting.desc}</label>
                        <select class="form-control" name="{$setting.name}" {$setting.params}>
                            {foreach from=$setting.values item=v key=k}
                                <option value="{$v}"
                                        {if $v == $setting.value}selected="selected"{/if}>{$setting.descs.$k}</option>
                            {/foreach}
                        </select>
                    </div>
                {elseif $setting.type == "radio"}
                    <div class="form-group">
                        <label for="{$setting.name}">{$setting.desc}</label>
                        <div class="radio">
                            {foreach name=radios item=radio from=$setting.value}
                                <label class="radio-inline"><input type="radio" name="{$setting.name}"
                                                                   value="{$radio}">{$radio}</label>
                            {/foreach}
                        </div>
                    </div>
                {elseif $setting.type == "doubleselect"}
                    <label>{$setting.desc}</label>
                    <div class="row">
                        <input type="hidden" name="{$setting.name}"
                               value="{$setting.avalue}"/>
                        <input type="hidden" name="old_{$setting.name}"
                               value="{$setting.avalue}"/>
                        <div class="col-sm-5">
                            <select class="form-control" name="sel_ds1_{$setting.name}" {$setting.params}
                                    onclick="double_select_add(this.options[parseInt(this.selectedIndex)].text, this.options[parseInt(this.selectedIndex)].value, 'sel_ds2_{$setting.name}', '{$setting.name}');">
                                {foreach from=$setting.values item=v key=k}
                                    <option value="{$v}">{$setting.descs.$k}</option>
                                {/foreach}
                            </select>

                        </div>
                        <div class="col-sm-2" style="text-align: center;">
                            {if $setting.show_moves}
                                <img src="{$ko_path}images/ds_top.gif" border="0"
                                     alt="up"
                                     onclick="double_select_move('{$setting.name}', 'top');"/>
                                <br/>
                                <img src="{$ko_path}images/ds_up.gif" border="0"
                                     alt="up"
                                     onclick="double_select_move('{$setting.name}', 'up');"/>
                                <br/>
                                <img src="{$ko_path}images/ds_down.gif" border="0"
                                     alt="up"
                                     onclick="double_select_move('{$setting.name}', 'down');"/>
                                <br/>
                                <img src="{$ko_path}images/ds_bottom.gif" border="0"
                                     alt="up"
                                     onclick="double_select_move('{$setting.name}', 'bottom');"/>
                                <br/>
                                <img src="{$ko_path}images/ds_del.gif" border="0"
                                     alt="up"
                                     onclick="double_select_move('{$setting.name}', 'del');"/>
                            {else}
                                <img src="{$ko_path}images/button_delete.gif"
                                     alt="{$label_doubleselect_remove}"
                                     title="{$label_doubleselect_remove}" border="0"
                                     onclick="double_select_move('{$setting.name}', 'del');"/>
                            {/if}

                        </div>
                        <div class="col-sm-5">
                            <select class="form-control" name="sel_ds2_{$setting.name}" {$setting.params}>
                                {foreach from=$setting.avalues item=v key=k}
                                    <option value="{$v}">{$setting.adescs.$k}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                {elseif $setting.type == "html"}
                    {$setting.value}
                {/if}
            {/foreach}
        </div>
    {/section}
</div>
<hr/>
<div class="form-group">
    <input class="btn btn-primary" type="submit" name="submit" value="{$label_save}"
           onclick="set_action('{$tpl_action}', this)"/>
    <input class="btn btn-default" type="reset" name="cancel" value="{$label_reset}"/>

</div>
