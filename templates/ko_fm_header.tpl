<div class="panel panel-default">
    <div class="panel-heading">
        <h5 class="panel-title">
            {if $tpl_fm_pos == 'm'}
            <a data-toggle="collapse" href="#{$tpl_fm_id}">
                {else}
            <a data-toggle="collapse" data-parent="#accordion" href="#{$tpl_fm_id}">
                {/if}
                {$tpl_fm_title}
            </a>
        </h5>
    </div>
    <div id="{$tpl_fm_id}" class="panel-collapse collapse {if $tpl_fm_pos == 'm'}in{/if} {if $tpl_fm_always_open}in{/if}">
        <div class="panel-body">
