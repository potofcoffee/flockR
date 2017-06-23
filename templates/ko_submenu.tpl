<div name="sm_{$sm.mod}_{$sm.id}" id="sm_{$sm.mod}_{$sm.id}" class="panel panel-default">
    <div class="panel-heading">
        <h5 class="panel-title">
            <a data-toggle="collapse" data-parent="#accordion" href="#sm_{$sm.mod}_{$sm.id}_collapse">
                {$sm.titel}</a>
        </h5>
    </div>
    <div id="sm_{$sm.mod}_{$sm.id}_collapse" class="panel-collapse collapse">
        <div class="panel-body">
            {if $sm.form}
            <form action="{if $sm.form_action}{$sm.form_action}{else}index.php{/if}"
                  method="{if $sm.form_method}{$sm.form_method}{else}POST{/if}">
                {foreach from=$sm.form_hidden_inputs item=h}
                    <input type="hidden" name="{$h.name}" value="{$h.value}"/>
                {/foreach}
                {/if}

                {foreach from=$sm.output item=i key=i_i}

                    {if $i==""}
                        <br/>
                    {else}
                        {if $i == "[itemlist]"}
                            {include file="ko_itemlist.tpl"}
                        {elseif $i == "[notizen]"}
                            {include file="ko_sm_notizen.tpl"}
                        {/if}

                        {if $sm.no_ul[$i_i] != TRUE}<strong><big>&middot;</big></strong>{/if}
                        {if $i == " "}
                            {$i}
                        {elseif $sm.link[$i_i] != ""}<a href="{$sm.link[$i_i]}">{$i}</a>
                            <br/>
                        {else}
                            {$i}
                            <br/>
                        {/if}

                        {if $sm.html[$i_i]}
                            {$sm.html[$i_i]}
                        {/if}

                    {/if}
                {/foreach}
                {if $sm.form}</form>{/if}
        </div>

    </div>
</div>