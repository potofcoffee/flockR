</div><!-- #wrapper -->
<footer>
    <div class="container">
        <div class="row">
            <div class="col-md-8">
                <a href="http://www.churchtool.org">kOOL - the church tool</a>
            </div>
            <div class="col-md-4">
                <p class="muted pull-right">
                    <?php
                    print strftime("%A&nbsp;-&nbsp;%x&nbsp;-&nbsp;%X") . '&nbsp;';
                    $help = ko_get_help($ko_menu_akt, "");
                    print $help["link"];
                    ?>
                </p>
            </div>
        </div>
    </div>
</footer>
