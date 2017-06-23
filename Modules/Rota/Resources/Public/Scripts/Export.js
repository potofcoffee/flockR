/**
 * Created by chris on 17.06.2017.
 */

var rebuildList;
var buildTemplateListHasRunOnce = 0;
var buildTemplateList = function () {
    if (buildTemplateListHasRunOnce) {
        // Remove the old unordered list from the dom.
        // This is just to cleanup the old list within the iframe
        $(this._.panel._.iframe.$).contents().find("ul").remove();
        // reset list
        this._.items = {};
        this._.list._.items = {};
    }
    var self = this;
    $.each(templates, function (index, value) {
        // value, html, text
        self.add(value.value, value.label, value.label)
    });
    if (buildTemplateListHasRunOnce) {
        // Force CKEditor to commit the html it generates through this.add
        this._.committed = 0; // We have to set to false in order to trigger a complete commit()
        this.commit();
    }
    buildTemplateListHasRunOnce = 1;
};


$(document).ready(function(){
    $('#doubleselect_teams').parent().parent().parent().hide();
    $('#doubleselect_individualRecipients').parent().parent().parent().hide();
    $('select[name="recipientOptions"]').on('change', function() {
        switch ($(this).val()) {
            case 'scheduled':
            case 'scheduledVisible':
            case 'visible':
            case 'visibleLeaders':
            case 'allMembers':
            case 'allLeaders':
                $('#doubleselect_teams').parent().parent().parent().hide();
                $('#doubleselect_individualRecipients').parent().parent().parent().hide();
                break;
            case 'selectedScheduled':
            case 'selectedMembers':
            case 'selectedLeaders':
                $('#doubleselect_teams').parent().parent().parent().show();
                $('#doubleselect_individualRecipients').parent().parent().parent().hide();
                break;
            case 'selectedPersons':
                $('#doubleselect_teams').parent().parent().parent().hide();
                $('#doubleselect_individualRecipients').parent().parent().parent().show();
                break;

        }
    });
});

$(document).ready(function () {

    /////////////////////////// CKEDITOR

    var editor = CKEDITOR.replace('text', {
        extraPlugins: 'dialog'
    });
    var config = editor.config;
    CKEDITOR.skinName = config.skin;

    editor.ui.addRichCombo('placeholders', {
        label: 'Platzhalter',
        title: 'Platzhalter',
        voiceLabel: 'Platzhalter',
        className: 'cke_placeholder',
        multiSelect: false,
        onClick: function (value) {
            editor.focus();
            editor.fire('saveSnapshot');
            editor.insertHtml('[[' + value + ']]');
            editor.fire('saveSnapshot');
        },
        panel: {
            css: [
                config.contentsCss,
                CKEDITOR.getUrl(CKEDITOR.skin.getPath('editor') + 'editor.css'),
            ],
        },
        init: function () {

            var self = this;
            $.each(placeholders, function (index, value) {
                // value, html, text
                self.add(value.key, '<b>[[' + value.key + ']]</b><br />' + value.label, value.labelText)
            });
        }
    });

    CKEDITOR.dialog.add('saveTemplateDialog', function (editor) {
        return {
            title: 'Als Vorlage speichern',
            minWidth: 400,
            minHeight: 100,
            contents: [
                {
                    id: 'general',
                    label: 'Settings',
                    elements: [
                        {
                            id: 'templateKey',
                            type: 'text',
                            label: 'Titel der neuen Vorlage',
                            validate: function () {
                                if (!this.getValue()) {
                                    alert('Du musst einen Titel eingeben.');
                                    return false;
                                }
                            }
                        },
                        {
                            type: 'checkbox',
                            id: 'saveAsGlobalTemplate',
                            label: 'Als globale Vorlage für alle Benutzer speichern',
                            'default': 'checked',
                        }
                    ]
                }
            ],
            onOk: function () {
                var dialog = this;
                var templateTitle = dialog.getValueOf('general', 'templateKey');
                var templateText = editor.getData();
                var templateIsGlobal = dialog.getValueOf('general', 'saveAsGlobalTemplate');

                $(templates).extend({
                        templateTitle: {
                            key: templateTitle,
                            label: templateIsGlobal ? '<span class="fa fa-globe"></span> ' + templateTitle : templateTitle,
                            labelText: templateTitle,
                            value: templateText,
                            userId: templateIsGlobal ? -1 : undefined,
                        }
                    }
                );
            }
        };
    });


    editor.addCommand('cmdSaveTemplateDialog', new CKEDITOR.dialogCommand('saveTemplateDialog'));

    /*
    editor.ui.addButton('saveAsTemplate',
        {
            label: 'Als neue Vorlage speichern',
            command: 'cmdSaveTemplateDialog',
            icon: baseUrl + 'Modules/Rota/Resources/Public/Images/CKEditor/fa-save.png',
        });
    */

    editor.ui.addRichCombo('templates', {
        label: 'Vorlagen',
        title: 'Vorlagen',
        voiceLabel: 'Vorlagen',
        className: 'cke_placeholders',
        multiSelect: false,
        panel: {
            css: [
                config.contentsCss,
                '//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css',
                CKEDITOR.getUrl(CKEDITOR.skin.getPath('editor') + 'editor.css')
            ],
        },
        onClick: function (value) {
            editor.focus();
            editor.fire('saveSnapshot');
            editor.insertHtml(value);
            editor.fire('saveSnapshot');
        },
        init: function () {
            rebuildList = CKEDITOR.tools.bind(buildTemplateList, this);
            rebuildList();
            $(editor).bind('rebuildList', rebuildList);
        }
    });


});
