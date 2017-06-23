
<div class="row">
    {foreach from=$mods item=module}
    <div class="col-lg-3 col-md-6">
        <div class="panel {if $module.count >0}panel-danger{else}panel-success{/if}">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3">
                        <i class="fa {$module.icon} fa-5x"></i>
                    </div>
                    <div class="col-xs-9 text-right">
                        <div class="huge">{$module.count}</div>
                        <div>{$module.label}</div>
                    </div>
                </div>
            </div>
            <a href="{$module.link}">
                <div class="panel-footer">
                    <span class="pull-left">Ansehen</span>
                    <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>

                    <div class="clearfix"></div>
                </div>
            </a>
        </div>
    </div>
    {/foreach}
</div>
