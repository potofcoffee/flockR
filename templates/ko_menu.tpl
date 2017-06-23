<div id="wrapper">
    <header>
        <nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
            <div class="navbar-header">
                <a class="navbar-brand" href="/"><span class="icon-flockr"></span> flockR</a>
            </div>

            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>

            <ul class="nav navbar-nav navbar-left navbar-collapse">
                <li>
                    <a href="/">
                        <span class="glyphicon glyphicon-home"></span>
                    </a>
                </li>
                {foreach from=$tpl_menu item=menu}
                    {if $tpl_menu_akt == $menu.id}
                        {assign var="post_akt" value="1"}
                        {assign var="a_class" value="active"}
                        {assign var="li_class" value="active"}
                    {else}
                        {assign var="a_class" value=""}
                        {assign var="li_class" value=""}
                        {if $post_akt == 1}
                            {assign var="a_class" value="post_akt"}
                        {/if}
                    {/if}
                    {if $menu.menu}
                        {assign var="li_class2" value="dropdown"}
                        <li class="{$li_class} dropdown"><a href="#" class="dropdown-toggle"
                                                            data-toggle="dropdown"
                                                            role="button" aria-haspopup="true"
                                                            aria-expanded="false">{$menu.name} <span
                                        class="caret"></span></a>
                            <ul class="dropdown-menu">
                                {foreach from=$menu.menu item=submenu}
                                    {if $submenu.link}
                                        <li><a href="{$ko_path}{$submenu.link}">{$submenu.name}</a></li>
                                    {else}
                                        <li class="dropdown-header">{$submenu.name}</li>
                                    {/if}
                                {/foreach}
                            </ul>
                        </li>
                    {else}
                        <li class="{$li_class} {$li_class2}"><a href="{$ko_path}{$menu.link}" {$menu.link_param}
                                                                title="{$menu.name}">{$menu.name}</a></li>
                    {/if}
                {/foreach}
            </ul>
            <ul class="nav navbar-right navbar-top-links">
                {if $isLoggedIn}
                    <li>
                        <a href="{$ko_path}core/admin/settings"><span class="fa fa-cog"></span></a>
                    </li>
                    <li class="dropdown pull-right">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown"
                           role="button" aria-haspopup="true"
                           aria-expanded="false"><i class="fa fa-user fa-fw"></i> {$logout.user}<span
                                    class="caret"></span></a>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="{$ko_path}core/admin/preferences"><i class="fa fa-cog"></i> Meine Einstellungen</a>
                            </li>
                            <li>
                                <a href="{$logout.link}"><i class="fa fa-sign-out fa-fw"></i> {$logout.text}</a>
                            </li>
                        </ul>
                    </li>
                {else}
                    <li class="dropdown pull-right">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown"
                           role="button" aria-haspopup="true"
                           aria-expanded="false"><i class="fa fa-user fa-fw"></i> {$login.text.login}<span
                                    class="caret"></span></a>
                        <ul class="dropdown-menu" id="login-dp">
                            <li>
                                <div class="row">
                                    <div class="col-md-12">
                                        <form method="post" action="{$ko_path}index.php">
                                            <div style="white-space:nowrap;">
                                                <div class="form-group">
                                                    <label for="exampleInputEmail2">{$login.text.username}</label>
                                                    <input type="text" name="username" class="form-control"
                                                           id="exampleInputEmail2"
                                                           placeholder="{$login.text.username}" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="exampleInputPassword2">{$login.text.password}</label>
                                                    <input type="password" name="password" class="form-control"
                                                           id="exampleInputPassword2"
                                                           placeholder="{$login.text.password}" required>
                                                </div>
                                                <div class="form-group">
                                                    <input type="submit" value="{$login.text.login}"
                                                           name="Login" class="btn btn-primary btn-block"/>
                                                </div>
                                        </form>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </li>
                {/if}
            </ul>
