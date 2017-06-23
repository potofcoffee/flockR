sfHover = function () {
    var b = document.getElementById("nav").getElementsByTagName("LI");
    for (var a = 0; a < b.length; a++) {
        b[a].onmouseover = function () {
            this.className += " sfhover"
        };
        b[a].onmouseout = function () {
            this.className = this.className.replace(new RegExp(" sfhover\\b"), "")
        }
    }
};
if (window.attachEvent) {
    window.attachEvent("onload", sfHover)
}
smHover = function () {
    try {
        var a = document.getElementById("sm").getElementsByTagName("LI");
        for (var b = 0; b < a.length; b++) {
            a[b].onmouseover = function () {
                this.className += " smhover"
            };
            a[b].onmouseout = function () {
                this.className = this.className.replace(new RegExp(" smhover\\b"), "")
            }
        }
    } catch (d) {
    }
};
if (window.attachEvent) {
    window.attachEvent("onload", smHover)
}
function set_ids_from_chk(b) {
    var a = "";
    $.each($("input[name^='chk[']:checked"), function (d, e) {
        a += e.name.replace("chk[", "").replace("]", "") + ","
    });
    a = a.slice(0, -1);
    set_hidden_value("ids", a, b)
}
function set_action(b, d) {
    if (d == null) {
        document.getElementById("action").value = b
    } else {
        jQuery(d).closest("form").find('input[name="action"]').attr("value", b)
    }
}
function set_hidden_value(a, d, b) {
    if (b == null) {
        document.getElementsByName(a)[0].value = d
    } else {
        jQuery(b).closest("form").find('input[name="' + a + '"]').attr("value", d)
    }
}
function double_select_add(l, h, a, j) {
    var d = document.getElementsByName(a)[0];
    for (var b = 0; b < d.length; b++) {
        if (d.options[b].value == h) {
            return
        }
    }
    var f = new Option(l, h);
    d.options[d.length] = f;
    var g = document.getElementsByName(j)[0];
    var e = document.getElementsByName(a)[0];
    g.value = "";
    for (var b = 0; b < e.length; b++) {
        if (e.options[b].value != "") {
            g.value += e.options[b].value + ","
        }
    }
    if (g.value != "") {
        g.value = g.value.slice(0, -1)
    }
}
function double_select_move(d, h) {
    var f = document.getElementsByName(("sel_ds2_" + d))[0];
    if (h == "del") {
        f.remove(f.selectedIndex)
    } else {
        if (h == "top") {
            x = -f.selectedIndex
        }
        if (h == "up") {
            x = -1
        }
        if (h == "down") {
            x = 1
        }
        if (h == "bottom") {
            x = f.options.length - f.selectedIndex - 1
        }
        var b = (sI = f.selectedIndex) + (x);
        if (b >= f.options.length || b < 0) {
            return
        }
        var g = f.options[sI];
        f.remove(sI);
        if (navigator.userAgent.indexOf("MSIE") != -1) {
            f.add(g, b)
        } else {
            f.add(g, f.options[b])
        }
        f.selectedIndex = b
    }
    var a = document.getElementsByName(d)[0];
    a.value = "";
    for (var e = 0; e < f.length; e++) {
        if (f.options[e].value != "") {
            a.value += f.options[e].value + ","
        }
    }
    if (a.value != "") {
        a.value = a.value.slice(0, -1)
    }
}
function do_fill_select() {
    if (http.readyState == 4) {
        if (http.status == 200) {
            responseText = http.responseText;
            split = responseText.split("@@@");
            el_id = split[0].trim();
            value = split[1];
            list = document.getElementsByName(el_id)[0];
            if (list) {
                for (var b = list.options.length - 1; b >= 0; b--) {
                    list.options[b] = null
                }
                var a = value.split("#");
                for (b = 0; b < a.length; b++) {
                    temp = a[b].split(",");
                    list.options[b] = new Option(temp[1], temp[0]);
                    if (temp[2]) {
                        list.options[b].title = temp[2]
                    }
                }
            }
        } else {
            if (http.status == 404) {
                alert("Request URL does not exist")
            }
        }
        msg = document.getElementsByName("wait_message")[0];
        msg.style.display = "none";
        document.body.style.cursor = "default"
    }
}
function change_vis(a) {
    obj = document.getElementById(a);
    if (obj.style.visibility == "hidden") {
        obj.style.visibility = "visible";
        obj.style.display = "block"
    } else {
        obj.style.visibility = "hidden";
        obj.style.display = "none"
    }
}
function change_vis_tr(a) {
    obj = document.getElementById(a);
    if (obj.style.display == "none") {
        obj.style.display = ""
    } else {
        obj.style.display = "none"
    }
}
function set_vis(a) {
    obj = document.getElementById(a);
    obj.style.visibility = "visible";
    obj.style.display = "block"
}
function unset_vis(a) {
    obj = document.getElementById(a);
    obj.style.visibility = "hidden";
    obj.style.display = "none"
}
function select_all_list_chk() {
    for (i = 0; i < document.formular.length; i++) {
        obj = document.formular.elements[i];
        if (obj.type == "checkbox" && obj.name.substring(0, 4) == "chk[") {
            obj.checked = !obj.checked
        } else {
            if (obj.type == "text" && obj.name.substring(0, 4) == "txt[") {
                if (!obj.value) {
                    obj.value = 1
                } else {
                    obj.value = Math.abs(obj.value) + 1
                }
            }
        }
    }
}
function openPic(b, a, e) {
    var d = window.open(b, a, e);
    if (d) {
        d.focus()
    }
}
function jumpToUrl(a) {
    document.location = a
}
String.prototype.trim = function () {
    return this.replace(/^\s*|\s*$/g, "")
};
function getMultiple(a) {
    selected = "";
    while (a.selectedIndex != -1) {
        selected += a.options[a.selectedIndex].value + "MULTIPLE";
        a.options[a.selectedIndex].selected = false
    }
    selected = selected.slice(0, -8);
    return selected
}
function exchangeComma(a) {
    if (a.value.match(";")) {
        while (a.value.match(";")) {
            a.value = a.value.replace(";", ",")
        }
    } else {
        while (a.value.match(",")) {
            a.value = a.value.replace(",", ";")
        }
    }
}
function form_set_first_input() {
    for (i = 0; i < document.formular.length; i++) {
        obj = document.formular.elements[i];
        if (obj.type != "hidden" && obj.name.substr(0, 3) == "koi") {
            obj.focus();
            return true
        }
    }
    for (i = 0; i < document.formular.length; i++) {
        obj = document.formular.elements[i];
        if (obj.type != "hidden" && obj.name != "sel_notiz" && obj.name != "txt_notiz" && obj.name != "txt_notiz_new") {
            obj.focus();
            return true
        }
    }
}
function form_set_focus(a) {
    obj = document.getElementsByName(a)[0];
    obj.focus()
}
function forAllHeader(a, b) {
    obj = document.getElementById(a);
    chk = document.getElementById(b);
    if (obj.style.visibility == "hidden") {
        state = "open";
        obj.style.visibility = "visible";
        obj.style.display = "block";
        chk.checked = "checked"
    } else {
        state = "closed";
        obj.style.visibility = "hidden";
        obj.style.display = "none";
        chk.checked = ""
    }
    divs = document.getElementsByTagName("div");
    for (i = 0; i < divs.length; i++) {
        obj = divs[i];
        if (obj.id.substring(0, 7) == "frmgrp_" && obj.id != "frmgrp_0") {
            if (state == "open") {
                obj.style.visibility = "hidden";
                obj.style.display = "none"
            } else {
                obj.style.visibility = "visible";
                obj.style.display = "block"
            }
        }
    }
}
var http = createRequestObject();
function createRequestObject(b) {
    try {
        request = new XMLHttpRequest()
    } catch (d) {
        try {
            request = new ActiveXObject("Msxml2.XMLHTTP")
        } catch (e) {
            try {
                request = new ActiveXObject("Microsoft.XMLHTTP")
            } catch (a) {
                request = false
            }
        }
    }
    if (!request) {
        alert("Error initializing XMLHttpRequest!")
    } else {
        return request
    }
}
function sendReq(url, keys, vals, success) {
    var arguments= "";
    keys = keys.split(",");
    vals = vals.split(",");
    for (i = 0; i < keys.length; i++) {
        arguments += keys[i] + "=" + vals[i] + "&"
    }
    arguments = arguments.substring(0, (arguments.length - 1));
    if (arguments.length == 0) {
        http.open("get", url)
    } else {
        http.open("get", url + "?" + arguments)
    }
    if (success) {
        http.onreadystatechange = success
    }
    http.send(null)
}
function do_element() {
    if (http.readyState == 1 || http.readyState == 2 || http.readyState == 3) {
        msg = document.getElementsByName("wait_message")[0];
        msg.style.display = "block";
        document.body.style.cursor = "wait"
    } else {
        if (http.readyState == 4) {
            if (http.status == 200) {
                responseText = http.responseText;
                if (responseText.substring(0, 8) == "ERROR@@@" || responseText.substring(0, 7) == "INFO@@@") {
                    split = responseText.split("@@@");
                    mode = split[0].trim();
                    value = split[1];
                    ko_infobox(mode, value);
                    msg = document.getElementsByName("wait_message")[0];
                    msg.style.display = "none";
                    document.body.style.cursor = "default";
                    return
                }
                if (responseText.substring(0, 11) == "DOWNLOAD@@@") {
                    split = responseText.split("@@@");
                    value = split[1];
                    ko_popup("../download.php?action=file&file=" + value);
                    msg = document.getElementsByName("wait_message")[0];
                    msg.style.display = "none";
                    document.body.style.cursor = "default";
                    return
                }
                postsplit = responseText.split("@@@POST@@@");
                responseText = postsplit[0];
                do_element_post = postsplit[1];
                split = responseText.split("@@@");
                el_id = split[0].trim();
                value = split[1];
                element = document.getElementsByName(el_id)[0];
                if (element) {
                    $(element).html(value)
                }
                if (split[2] && split[3]) {
                    el2_id = split[2].trim();
                    value2 = split[3];
                    element2 = document.getElementsByName(el2_id)[0];
                    if (element2) {
                        $(element2).html(value2)
                    }
                }
                if (split[4] && split[5]) {
                    el3_id = split[4].trim();
                    value3 = split[5];
                    element3 = document.getElementsByName(el3_id)[0];
                    if (element3) {
                        $(element3).html(value3)
                    }
                }
                if (do_element_post == "filter_group") {
                    initList(1, document.getElementsByName("var1")[0])
                } else {
                    eval(do_element_post)
                }
            } else {
                if (http.status == 404) {
                    alert("Request URL does not exist")
                }
            }
            msg = document.getElementsByName("wait_message")[0];
            msg.style.display = "none";
            document.body.style.cursor = "default"
        }
    }
}
function show_box() {
    if (http.readyState == 1 || http.readyState == 2 || http.readyState == 3) {
        msg = document.getElementsByName("wait_message")[0];
        msg.style.display = "block";
        document.body.style.cursor = "wait"
    } else {
        if (http.readyState == 4) {
            if (http.status == 200) {
                responseText = http.responseText;
                if (responseText.substring(0, 8) == "ERROR@@@") {
                    split = responseText.split("@@@");
                    mode = split[0].trim();
                    value = split[1];
                    ko_infobox(mode, value);
                    msg = document.getElementsByName("wait_message")[0];
                    msg.style.display = "none";
                    document.body.style.cursor = "default";
                    return
                }
                if (responseText.indexOf("@@@") == -1) {
                    url = responseText.split("@@@");
                    x = y = ""
                } else {
                    split = responseText.split("@@@");
                    url = split[0].trim();
                    x = split[1].trim();
                    y = split[2].trim()
                }
                ko_popup(url, x, y)
            } else {
                if (http.status == 404) {
                    alert("Request URL does not exist")
                }
            }
            msg = document.getElementsByName("wait_message")[0];
            msg.style.display = "none";
            document.body.style.cursor = "default"
        }
    }
}
if (typeof jQuery != "undefined") {
    $(document).ready(function () {
        $.getScript("/inc/tooltip.js");
        $(".input_switch").click(function () {
            if ($(this).hasClass("switch_state_0")) {
                $(this).removeClass("switch_state_0");
                $(this).addClass("switch_state_1");
                $(this).children(".switch_state_label_0").hide();
                $(this).children(".switch_state_label_1").show();
                $("#" + $(this).attr("name").slice(7)).attr("value", 1)
            } else {
                $(this).removeClass("switch_state_1");
                $(this).addClass("switch_state_0");
                $(this).children(".switch_state_label_1").hide();
                $(this).children(".switch_state_label_0").show();
                $("#" + $(this).attr("name").slice(7)).attr("value", 0)
            }
        });
        $("table.ko_list").find("td").on("click", function () {
            fullid = $(this).attr("id");
            if (typeof(fullid) != "undefined") {
                temp = fullid.split("|");
                table = temp[0];
                id = temp[1];
                col = temp[2];
                if (id > 0) {
                    if ($("#chk\\[" + id + "\\]").attr("checked")) {
                        $("#chk\\[" + id + "\\]").attr("checked", false)
                    } else {
                        $("#chk\\[" + id + "\\]").attr("checked", true)
                    }
                }
            }
        });
        $("table.ko_list").find("td").on("dblclick", function () {
            $("div.inlineform").each(function () {
                $(this).hide()
            });
            fullid = $(this).attr("id");
            temp = fullid.split("|");
            table = temp[0];
            id = temp[1];
            col = temp[2];
            if (table != "" && id > 0 && col != "") {
                sendReq("../inc/ajax.php", "action,id,module,sesid", "inlineform," + fullid + "," + kOOL.module + "," + kOOL.sid, inlineform_show)
            }
        });
        $(".inlineform textarea, .inlineform input, .inlineform select").on("blur", function () {
            if ($(this).hasClass("if-noblur")) {
                return
            }
            fullid = $(this).parents(".inlineform").attr("id").slice(3);
            sendReq("../inc/ajax.php", "action,id,module,sesid", "inlineformblur," + fullid + "," + kOOL.module + "," + kOOL.sid, inlineform_show)
        });
        $(".inlineform textarea, .inlineform input").on("dblclick", function (b) {
            b.cancelBubble;
            b.returnValue = false;
            if (this.is_ie === false) {
                b.preventDefault()
            }
            return false
        });
        $(".inlineform textarea, .inlineform input").on("click", function (b) {
            if ($(this).hasClass("if_submit")) {
            } else {
                b.cancelBubble;
                b.returnValue = false;
                if (this.is_ie === false) {
                    b.preventDefault()
                }
                return false
            }
        });
        $(".inlineform textarea, .inlineform input, .inlineform select").on("keyup", function (b) {
            fullid = $(this).parents(".inlineform").attr("id").slice(3);
            if (b.which == 27) {
                sendReq("../inc/ajax.php", "action,id,module,sesid", "inlineformblur," + fullid + "," + kOOL.module + "," + kOOL.sid, inlineform_show)
            } else {
                if (b.which == 13 && b.shiftKey == false) {
                    inlineform_submit(this, fullid)
                }
            }
        });
        $(".inlineform textarea, .inlineform input, .inlineform select").on("keydown", function (b) {
            if (b.which == 13 && b.shiftKey == false) {
                b.cancelBubble;
                b.returnValue = false;
                if (this.is_ie === false) {
                    b.preventDefault()
                }
                return false
            }
        });
        $(".inlineform select").on("change", function (b) {
            if ($(this).parents(".inlineform").hasClass("if-doubleselect")) {
                return
            }
            fullid = $(this).parents(".inlineform").attr("id").slice(3);
            inlineform_submit(this, fullid)
        });
        $(".inlineform input[type=button].if_submit").on("click", function (b) {
            fullid = $(this).parents(".inlineform").attr("id").slice(3);
            inlineform_submit(this, fullid)
        });
        var a;
        $("#ko_list_colitemlist_click").on("click", function () {
            if ($("#ko_list_colitemlist_flyout").css("display") == "none") {
                $("#ko_list_colitemlist_flyout").show()
            } else {
                $("#ko_list_colitemlist_flyout").hide()
            }
        });
        $(".flyout_header").on("click", function () {
            $("#ko_list_colitemlist_flyout").hide()
        });
        $("#ko_list_colitemlist_click").on("mouseover", function () {
            $(this).css({backgroundPosition: "-37px 0px"})
        });
        $("#ko_list_colitemlist_click").on("mouseout", function () {
            $(this).css({backgroundPosition: "0px 0px"})
        });
        $("div.input_clearer").click(function () {
            $(this).parent().find("input").val("").submit()
        });
        $(".access_apply_all").click(function () {
            name = "sel_" + $(this).attr("id");
            sel = document.getElementsByName(name)[0];
            val = sel.options[sel.selectedIndex].value;
            $(this).closest("div.form_divider").find("select").each(function () {
                $(this).val(val)
            })
        });
        $("input.textmultiplus_new").keypress(function (b) {
            if (b.keyCode == 13) {
                b.preventDefault();
                text = $(this).val();
                hid_name = $(this).attr("name").substr(4);
                name = "sel_ds2_" + hid_name;
                double_select_add(text, text, name, hid_name);
                $(this).val("");
                b.cancelBubble;
                return false
            }
        });
        $("body").on("click", "span.form_ft_new", function () {
            after = $(this).attr("data-after");
            field = $(this).attr("data-field");
            pid = $(this).attr("data-pid");
            sendReq("/inc/ajax.php", "action,field,pid,after,sesid", "ftnew," + field + "," + pid + "," + after + "," + kOOL.sid, do_element)
        });
        $("body").on("click", "button.form_ft_load_preset", function () {
            after = $(this).attr("data-after");
            field = $(this).attr("data-field");
            pid = $(this).attr("data-pid");
            preset_table = $(this).attr("data-preset-table");
            preset_join_value_local = $(this).attr("data-preset-join-value-local");
            preset_join_column_foreign = $(this).attr("data-preset-join-column-foreign");
            if (preset_join_value_local == null || preset_join_value_local == "") {
                console.log(kota_ft_alert_no_join_value);
                alert(kota_ft_alert_no_join_value[field])
            } else {
                sendReq("/inc/ajax.php", "action,field,pid,after,preset_table,join_value_local,join_column_foreign,sesid", "ftloadpresets," + field + "," + pid + "," + after + "," + preset_table + "," + preset_join_value_local + "," + preset_join_column_foreign + "," + kOOL.sid, do_element)
            }
            return false
        });
        $("body").on("click", "input.form_ft_save", function (b) {
            b.preventDefault();
            after = $(this).attr("data-after");
            field = $(this).attr("data-field");
            table = $(this).attr("data-table");
            pid = $(this).attr("data-pid");
            id = $(this).attr("data-id");
            if (!id) {
                id = 0
            }
            action = id > 0 ? "ftedit" : "ftsave";
            formData = new FormData();
            formData.append("action", action);
            formData.append("field", field);
            formData.append("id", id);
            formData.append("pid", pid);
            formData.append("after", after);
            formData.append("sesid", kOOL.sid);
            fields = $(this).attr("data-fields").split(",");
            for (i = 0; i < fields.length; i++) {
                el = document.getElementsByName("koi[" + table + "][" + fields[i] + "][" + id + "]")[0];
                if (el.type == "file") {
                    files = el.files;
                    if (files.length > 0) {
                        formData.append("koi[" + table + "][" + fields[i] + "][" + id + "]", files[0], files[0].name)
                    }
                    elDel = document.getElementsByName("koi[" + table + "][" + fields[i] + "_DELETE][" + id + "]")[0];
                    if ($(elDel).attr("checked")) {
                        formData.append(fields[i] + "_DELETE", 1)
                    }
                } else {
                    formData.append(fields[i], $(el).val())
                }
            }
            $.ajax({
                type: "POST",
                url: "../inc/ajax.php",
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                success: function (d) {
                    split = d.split("@@@");
                    el_id = split[0].trim();
                    value = split[1];
                    element = document.getElementsByName(el_id)[0];
                    if (element) {
                        $(element).html(value)
                    }
                    $(".richtexteditor").ckeditor({customConfig: "/" + kOOL.module + "/inc/ckeditor_custom_config.js"})
                }
            })
        });
        $("body").on("change", "input,select,textarea", function (b) {
            $(this).parents("div.form_ft_row").addClass("form_ft_row_changed")
        });
        $("body").on("click", "img.form_ft_add", function (b) {
            after = $(this).attr("data-after");
            field = $(this).attr("data-field");
            pid = $(this).attr("data-pid");
            sendReq("/inc/ajax.php", "action,field,pid,after,sesid", "ftnew," + field + "," + pid + "," + after + "," + kOOL.sid, do_element)
        });
        $("body").on("click", "img.form_ft_delete", function (b) {
            field = $(this).attr("data-field");
            id = $(this).attr("data-id");
            pid = $(this).attr("data-pid");
            sendReq("/inc/ajax.php", "action,field,pid,id,sesid", "ftdelete," + field + "," + pid + "," + id + "," + kOOL.sid, do_element)
        });
        $("body").on("click", "img.form_ft_moveup", function (b) {
            field = $(this).attr("data-field");
            id = $(this).attr("data-id");
            pid = $(this).attr("data-pid");
            sendReq("/inc/ajax.php", "action,field,pid,id,direction,sesid", "ftmove," + field + "," + pid + "," + id + ",up," + kOOL.sid, do_element)
        });
        $("body").on("click", "img.form_ft_movedown", function (b) {
            field = $(this).attr("data-field");
            id = $(this).attr("data-id");
            pid = $(this).attr("data-pid");
            sendReq("/inc/ajax.php", "action,field,pid,id,direction,sesid", "ftmove," + field + "," + pid + "," + id + ",down," + kOOL.sid, do_element)
        });
        $("body").on("change", "select.sel-peoplefilter", function (b) {
            fid = $(this).val();
            field = $(this).attr("name").substring(18);
            sendReq("/inc/ajax.php", "action,field,fid,sesid", "peoplefilterform," + field + "," + fid + "," + kOOL.sid, do_element)
        });
        $("body").on("click", ".peoplefilter-submit", function (b) {
            fid = $("select.sel-peoplefilter").val();
            var1 = $("div.filter-form [name=var1]").val();
            if (typeof var1 === "undefined") {
                var1 = ""
            }
            text1 = $("div.filter-form select[name=var1] option:selected").text();
            if (typeof text1 === "undefined") {
                text1 = ""
            }
            var2 = $("div.filter-form [name=var2]").val();
            if (typeof var2 === "undefined") {
                var2 = ""
            }
            text2 = $("div.filter-form select[name=var2] option:selected").text();
            if (typeof text2 === "undefined") {
                text2 = ""
            }
            var3 = $("div.filter-form [name=var3]").val();
            if (typeof var3 === "undefined") {
                var3 = ""
            }
            text3 = $("div.filter-form select[name=var3] option:selected").text();
            if (typeof text3 === "undefined") {
                text3 = ""
            }
            neg = $("div.filter-form input[name=filter_negativ]").attr("checked") ? 1 : 0;
            newV = fid + "|" + var1 + "|" + var2 + "|" + var3 + "|" + neg;
            text = $("select.sel-peoplefilter option:selected").text() + ": ";
            if (neg) {
                text += "!"
            }
            if (text1) {
                text += text1
            } else {
                if (var1) {
                    text += var1
                }
            }
            if (text2) {
                text += "," + text2
            } else {
                if (var2) {
                    text += "," + var2
                }
            }
            if (text3) {
                text += "," + text3
            } else {
                if (var3) {
                    text += "," + var3
                }
            }
            $("select.peoplefilter-act").append($("<option></option>").val(newV).html(text));
            value = "";
            $("select.peoplefilter-act option").each(function () {
                value += $(this).val() + ","
            });
            $("input.peoplefilter-value").val(value.slice(0, -1));
            return false
        });
        $("div.koi-checkboxes-entry").click(function () {
            if ($(this).children("input").attr("checked")) {
                $(this).children("input").attr("checked", false);
                $(this).removeClass("koi-checkboxes-checked")
            } else {
                $(this).children("input").attr("checked", true);
                $(this).addClass("koi-checkboxes-checked")
            }
            value = "";
            $(this).parent("div.koi-checkboxes-container").find("input:checked").each(function () {
                value += (value != "" ? "," : "") + $(this).val()
            });
            $(this).parent("div.koi-checkboxes-container").children("input.koi-checkboxes-value").val(value)
        });
        $("div.koi-checkboxes-entry input").click(function (b) {
            if ($(this).is(":checked")) {
                $(this).attr("checked", false)
            } else {
                $(this).attr("checked", true)
            }
        })
    })
}
function inlineform_submit(obj, fullid) {
    submit_cols = new Array("action", "id", "module", "sesid");
    submit_values = new Array("inlineformsubmit", fullid, kOOL.module, kOOL.sid);
    c = 4;
    $(obj).parents(".inlineform").find("input[type=text][name^=koi], input[type=hidden][name^=koi], textarea[name^=koi]").each(function () {
        submit_cols[c] = $(this).attr("name");
        submit_values[c] = encodeURIComponent($(this).val().replace(new RegExp(",", "g"), "|").replace(new RegExp("\n", "g"), "<br />"));
        c++
    });
    $(obj).parents(".inlineform").find("select[name^=koi]").each(function () {
        submit_cols[c] = $(this).attr("name");
        submit_values[c] = $(this).val().replace(new RegExp(",", "g"), "|");
        c++
    });
    var params = {};
    for (i = 0; i < submit_cols.length; i++) {
        params[submit_cols[i]] = submit_values[i]
    }
    $.get("../inc/ajax.php", params, function (data) {
        responseText = data;
        split = responseText.split("@@@");
        k = 0;
        while (split[k]) {
            el_id = split[k].trim();
            value = split[k + 1];
            if (el_id == "ERROR") {
                ko_infobox(el_id, value)
            } else {
                if (el_id != "") {
                    element = document.getElementById(el_id);
                    if (element) {
                        js_code = new Array();
                        while (value.indexOf('<script type="text/javascript">') > -1) {
                            start = value.indexOf('<script type="text/javascript">');
                            stop = value.indexOf("<\/script>") + 9;
                            js_code.push(value.substring(start, stop).replace(new RegExp('<script type="text/javascript">'), "").replace(new RegExp("<\/script>"), ""));
                            value = value.substring(0, start) + value.substring(stop)
                        }
                        element.innerHTML = value;
                        if_element = document.getElementById("if_" + el_id);
                        if (if_element) {
                            if ($(if_element).find("input, textarea, select").length > 1 || $(if_element).find("input.jsdate-input").length > 0) {
                                $(if_element).find("input, textarea, select").addClass("if-noblur")
                            }
                            $(if_element).find("input, textarea, select").first().focus()
                        }
                        if (js_code.length > 0) {
                            for (i = 0; i < js_code.length; i++) {
                                eval(js_code[i])
                            }
                        }
                    }
                }
            }
            k = k + 2
        }
    }).fail(function (e) {
        console.log(e)
    })
}
function inlineform_show() {
    if (http.readyState == 4) {
        if (http.status == 200) {
            responseText = http.responseText;
            split = responseText.split("@@@");
            k = 0;
            while (split[k]) {
                el_id = split[k].trim();
                value = split[k + 1];
                if (el_id == "ERROR") {
                    ko_infobox(el_id, value)
                } else {
                    if (el_id != "") {
                        element = document.getElementById(el_id);
                        if (element) {
                            js_code = new Array();
                            while (value.indexOf('<script type="text/javascript">') > -1) {
                                start = value.indexOf('<script type="text/javascript">');
                                stop = value.indexOf("<\/script>") + 9;
                                js_code.push(value.substring(start, stop).replace(new RegExp('<script type="text/javascript">'), "").replace(new RegExp("<\/script>"), ""));
                                value = value.substring(0, start) + value.substring(stop)
                            }
                            element.innerHTML = value;
                            if_element = document.getElementById("if_" + el_id);
                            if (if_element) {
                                if ($(if_element).find("input, textarea, select").length > 1 || $(if_element).find("input.jsdate-input").length > 0) {
                                    $(if_element).find("input, textarea, select").addClass("if-noblur")
                                }
                                $(if_element).find("input, textarea, select").first().focus()
                            }
                            if (js_code.length > 0) {
                                for (i = 0; i < js_code.length; i++) {
                                    eval(js_code[i])
                                }
                            }
                        }
                    }
                }
                k = k + 2
            }
        } else {
            if (http.status == 404) {
                alert("Request URL does not exist")
            }
        }
        msg = document.getElementsByName("wait_message")[0];
        msg.style.display = "none";
        document.body.style.cursor = "default"
    }
}
function kota_show_filter(b, a) {
}
var peoplesearchTimer;
$(document).ready(function () {
    $(".ko_listh_filter").on("contextmenu", function (a) {
        $("#ko_listh_filterbox").hide();
        all = $(this).attr("id").substring(6);
        split = all.split(":");
        table = split[0].trim();
        cols = split[1].trim();
        $.get("../inc/ajax.php", {
            action: "kotafilter",
            module: kOOL.module,
            table: table,
            cols: cols,
            sesid: kOOL.sid
        }, function (b) {
            if (b != "") {
                $("#ko_listh_filterbox").css({top: a.pageY + "px", left: a.pageX + "px"}).html(b).show()
            }
        });
        return false
    });
    $("#ko_listh_filterbox").on("click", function (a) {
        a.stopPropagation()
    });
    $(document).click(function () {
        $("#ko_listh_filterbox").hide()
    });
    $("#kota_filterbox_submit").on("click", function (a) {
        a.preventDefault();
        submit_cols = new Array("action", "module", "sesid");
        submit_values = new Array("kotafiltersubmit", kOOL.module, kOOL.sid);
        c = 3;
        submit_cols[c] = "neg";
        submit_values[c] = $("#kota_filterbox_neg").attr("checked") ? 1 : 0;
        c++;
        $(".kota_filter_inputs").each(function () {
            submit_cols[c] = $(this).attr("name");
            submit_values[c] = $(this).val().replace(new RegExp(",", "g"), "|");
            c++
        });
        sendReq("../inc/ajax.php", submit_cols.join(","), submit_values.join(","), do_element)
    });
    $("#kota_filterbox_clear").on("click", function (a) {
        a.preventDefault();
        sendReq("../inc/ajax.php", "action,module,sesid,id", "kotafilterclear," + kOOL.module + "," + kOOL.sid + "," + $(this).attr("rel").replace(new RegExp(",", "g"), "|"), do_element)
    });
    $(".peoplesearch").keyup(function (b) {
        if (b.keyCode == 40) {
            $(this).parent(".peoplesearchwrap").find("select").focus();
            $(this).parent(".peoplesearchwrap").find("select option:first-child").attr("selected", "selected")
        } else {
            var a = this;
            clearTimeout(peoplesearchTimer);
            peoplesearchTimer = setTimeout(function () {
                token = $(a).attr("data-source");
                $.get("../leute/inc/ajax.php", {
                    action: "peoplesearch",
                    string: $(a).val(),
                    name: $(a).attr("name"),
                    token: token,
                    sesid: kOOL.sid
                }, function (d) {
                    $(a).parent(".peoplesearchwrap").find("select.peoplesearchresult").html(d);
                    $(a).parent(".peoplesearchwrap").find(".peoplesearchresult").show()
                })
            }, 200)
        }
    });
    $(".peoplesearchresult").keypress(function (a) {
        if ($(this).find("option:first-child").attr("selected") == "selected" && a.keyCode == 38) {
            $(this).parent(".peoplesearchwrap").find("input.peoplesearch").focus()
        }
    });
    $("select.peoplesearchresult").keypress(function (a) {
        if (a.keyCode == 13) {
            a.preventDefault();
            name = $(this).attr("name").slice(8);
            value = $(this).children("[selected]").val();
            label = $(this).children("[selected]").attr("label");
            double_select_add(label, value, "sel_ds2_" + name, name);
            $(this).parent(".peoplesearchwrap").find("input.peoplesearch").focus()
        }
    });
    $("select.peoplesearchresult").click(function (a) {
        name = $(this).attr("name").slice(8);
        if (this.selectedIndex >= 0) {
            value = this.options[this.selectedIndex].value;
            label = this.options[this.selectedIndex].text;
            double_select_add(label, value, "sel_ds2_" + name, name)
        }
    });
    $("body").on("sortupdate", "table.ko_list.sortable tbody", function (a, b) {
        diff = Math.round((b.position.top - b.originalPosition.top) / b.item.height());
        id = b.item.attr("data-id");
        table = b.item.closest("table.ko_list.sortable").attr("data-table");
        sendReq("../inc/ajax.php", "action,table,module,id,diff,sesid", "tablesort," + table + "," + kOOL.module + "," + id + "," + diff + "," + kOOL.sid, do_element)
    })
});
function ko_infobox(b, a) {
    //TINY.box.show({html: a, animate: false, close: false, mask: false, boxid: "ko_infobox_" + b, autohide: 5, top: 0})
    $('body').append(a);
    $('#ko_infobox_'+b).modal('show');
}
function popup(e, a, b) {
    ko_popup(e, a, b)
}
function ko_popup(url, width, height) {
    width = width ? width : 350;
    height = height ? height : 200;
    //TINY.box.show({url: e, animate: true, close: true, mask: true, width: a, height: b})
    $('body').append('<div id="myModal" class="modal fade"><div class="modal-dialog"><div class="modal-content"></div></div></div>');
    $('#myModal .modal-content').load(url);
    $('#myModal').modal('show');
    $('#myModal').on('hidden.bs.modal', function () {
        $(this).remove();
    })
}
function ko_image_popup(a) {
    TINY.box.show({image: a, animate: true, close: true, mask: true})
}
function textarea_insert_text(e, d) {
    el = document.getElementById(e);
    inserttext = unescape(d);
    textAreaScrollPosition = el.scrollTop;
    if (document.selection) {
        el.focus();
        sel = document.selection.createRange();
        sel.text = inserttext
    } else {
        if (el.selectionStart || el.selectionStart == "0") {
            el.focus();
            var b = el.selectionStart;
            var a = el.selectionEnd;
            el.value = el.value.substring(0, b) + inserttext + el.value.substring(a, el.value.length);
            el.setSelectionRange(a + inserttext.length, a + inserttext.length)
        } else {
            el.value += inserttext
        }
    }
    el.scrollTop = textAreaScrollPosition
}
function richtexteditor_insert_text(a, d) {
    for (var b in CKEDITOR.instances) {
        if (CKEDITOR.instances[b].name == a) {
            CKEDITOR.instances[b].insertText(decodeURIComponent(d))
        }
    }
}
function richtexteditor_insert_html(b, a) {
    for (var d in CKEDITOR.instances) {
        if (CKEDITOR.instances[d].name == b) {
            CKEDITOR.instances[d].insertHtml(decodeURIComponent(a))
        }
    }
}
function do_fill_grouproles_select_filter() {
    if (http.readyState == 4) {
        if (http.status == 200) {
            responseText = http.responseText;
            list = document.getElementsByName("var2")[0];
            for (var b = list.options.length - 1; b >= 0; b--) {
                list.options[b] = null
            }
            var a = responseText.split("#");
            for (b = 0; b < a.length; b++) {
                temp = a[b].split(",");
                list.options[b] = new Option(temp[1], temp[0])
            }
        } else {
            if (http.status == 404) {
                alert("Request URL does not exist")
            }
        }
        msg = document.getElementsByName("wait_message")[0];
        msg.style.display = "none";
        document.body.style.cursor = "default"
    }
}
TINY = {};
TINY.box = function () {
    var f, d, a, h, e, l = 0;
    return {
        show: function (b) {
            e = {
                opacity: 70,
                close: 1,
                animate: 1,
                fixed: 1,
                mask: 1,
                maskid: "",
                boxid: "",
                topsplit: 2,
                url: 0,
                post: 0,
                height: 0,
                width: 0,
                html: 0,
                iframe: 0
            };
            for (s in b) {
                e[s] = b[s]
            }
            if (!l) {
                f = document.createElement("div");
                f.className = "tbox";
                l = document.createElement("div");
                l.className = "tinner";
                a = document.createElement("div");
                a.className = "tcontent";
                d = document.createElement("div");
                d.className = "tmask";
                h = document.createElement("div");
                h.className = "tclose";
                h.v = 0;
                document.body.appendChild(d);
                document.body.appendChild(f);
                f.appendChild(l);
                l.appendChild(a);
                d.onclick = h.onclick = TINY.box.hide;
                window.onresize = TINY.box.resize
            } else {
                f.style.display = "none";
                clearTimeout(l.ah);
                if (h.v) {
                    l.removeChild(h);
                    h.v = 0
                }
            }
            l.id = e.boxid;
            d.id = e.maskid;
            f.style.position = e.fixed ? "fixed" : "absolute";
            if (e.html && !e.animate) {
                l.style.backgroundImage = "none";
                a.innerHTML = e.html;
                a.style.display = "";
                l.style.width = e.width ? e.width + "px" : "auto";
                l.style.height = e.height ? e.height + "px" : "auto"
            } else {
                a.style.display = "none";
                if (!e.animate && e.width && e.height) {
                    l.style.width = e.width + "px";
                    l.style.height = e.height + "px"
                } else {
                    l.style.width = l.style.height = "100px"
                }
            }
            if (e.mask) {
                this.mask();
                this.alpha(d, 1, e.opacity)
            } else {
                this.alpha(f, 1, 100)
            }
            if (e.autohide) {
                l.ah = setTimeout(TINY.box.hide, 1000 * e.autohide)
            } else {
                document.onkeyup = TINY.box.esc
            }
        }, fill: function (q, n, m, j, g, p) {
            if (n) {
                if (e.image) {
                    var o = new Image();
                    o.onload = function () {
                        g = g || o.width;
                        p = p || o.height;
                        TINY.box.psh(o, j, g, p)
                    };
                    o.src = e.image
                } else {
                    if (e.iframe) {
                        this.psh('<iframe src="' + e.iframe + '" width="' + e.width + '" frameborder="0" height="' + e.height + '"></iframe>', j, g, p)
                    } else {
                        var b = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP");
                        b.onreadystatechange = function () {
                            if (b.readyState == 4 && b.status == 200) {
                                l.style.backgroundImage = "";
                                TINY.box.psh(b.responseText, j, g, p)
                            }
                        };
                        if (m) {
                            b.open("POST", q, true);
                            b.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                            b.send(m)
                        } else {
                            b.open("GET", q, true);
                            b.send(null)
                        }
                    }
                }
            } else {
                this.psh(q, j, g, p)
            }
        }, psh: function (o, j, g, m) {
            if (typeof o == "object") {
                a.appendChild(o)
            } else {
                a.innerHTML = o
            }
            var b = l.style.width, n = l.style.height;
            if (!g || !m) {
                l.style.width = g ? g + "px" : "";
                l.style.height = m ? m + "px" : "";
                a.style.display = "";
                if (!m) {
                    m = parseInt(a.offsetHeight)
                }
                if (!g) {
                    g = parseInt(a.offsetWidth)
                }
                a.style.display = "none"
            }
            l.style.width = b;
            l.style.height = n;
            this.size(g, m, j)
        }, esc: function (b) {
            b = b || window.event;
            if (b.keyCode == 27) {
                TINY.box.hide()
            }
        }, hide: function () {
            TINY.box.alpha(f, -1, 0, 3);
            document.onkeypress = null;
            if (e.closejs) {
                e.closejs()
            }
        }, resize: function () {
            TINY.box.pos();
            TINY.box.mask()
        }, mask: function () {
            d.style.height = this.total(1) + "px";
            d.style.width = this.total(0) + "px"
        }, pos: function () {
            var b;
            if (typeof e.top != "undefined") {
                b = e.top
            } else {
                b = (this.height() / e.topsplit) - (f.offsetHeight / 2);
                b = b < 20 ? 20 : b
            }
            if (!e.fixed && !e.top) {
                b += this.top()
            }
            f.style.top = b + "px";
            f.style.left = typeof e.left != "undefined" ? e.left + "px" : (this.width() / 2) - (f.offsetWidth / 2) + "px"
        }, alpha: function (g, j, b) {
            clearInterval(g.ai);
            if (j) {
                g.style.opacity = 0;
                g.style.filter = "alpha(opacity=0)";
                g.style.display = "block";
                TINY.box.pos()
            }
            g.ai = setInterval(function () {
                TINY.box.ta(g, b, j)
            }, 20)
        }, ta: function (g, b, m) {
            var j = Math.round(g.style.opacity * 100);
            if (j == b) {
                clearInterval(g.ai);
                if (m == -1) {
                    g.style.display = "none";
                    g == f ? TINY.box.alpha(d, -1, 0, 2) : a.innerHTML = l.style.backgroundImage = ""
                } else {
                    if (g == d) {
                        this.alpha(f, 1, 100)
                    } else {
                        f.style.filter = "";
                        TINY.box.fill(e.html || e.url, e.url || e.iframe || e.image, e.post, e.animate, e.width, e.height)
                    }
                }
            } else {
                var p = b - Math.floor(Math.abs(b - j) * 0.5) * m;
                g.style.opacity = p / 100;
                g.style.filter = "alpha(opacity=" + p + ")"
            }
        }, size: function (g, m, b) {
            if (b) {
                clearInterval(l.si);
                var j = parseInt(l.style.width) > g ? -1 : 1, n = parseInt(l.style.height) > m ? -1 : 1;
                l.si = setInterval(function () {
                    TINY.box.ts(g, j, m, n)
                }, 20)
            } else {
                l.style.backgroundImage = "none";
                if (e.close) {
                    l.appendChild(h);
                    h.v = 1
                }
                l.style.width = g + "px";
                l.style.height = m + "px";
                a.style.display = "";
                this.pos();
                if (e.openjs) {
                    e.openjs()
                }
            }
        }, ts: function (g, n, m, o) {
            var b = parseInt(l.style.width), j = parseInt(l.style.height);
            if (b == g && j == m) {
                clearInterval(l.si);
                l.style.backgroundImage = "none";
                a.style.display = "block";
                if (e.close) {
                    l.appendChild(h);
                    h.v = 1
                }
                if (e.openjs) {
                    e.openjs()
                }
            } else {
                if (b != g) {
                    l.style.width = (g - Math.floor(Math.abs(g - b) * 0.6) * n) + "px"
                }
                if (j != m) {
                    l.style.height = (m - Math.floor(Math.abs(m - j) * 0.6) * o) + "px"
                }
                this.pos()
            }
        }, top: function () {
            return document.documentElement.scrollTop || document.body.scrollTop
        }, width: function () {
            return self.innerWidth || document.documentElement.clientWidth || document.body.clientWidth
        }, height: function () {
            return self.innerHeight || document.documentElement.clientHeight || document.body.clientHeight
        }, total: function (m) {
            var g = document.body, j = document.documentElement;
            return m ? Math.max(Math.max(g.scrollHeight, j.scrollHeight), Math.max(g.clientHeight, j.clientHeight)) : Math.max(Math.max(g.scrollWidth, j.scrollWidth), Math.max(g.clientWidth, j.clientWidth))
        }
    }
}();


// added by Christoph Fischer <christoph.fischer@volksmission.de>
$(document).ready(function () {
    // open first submenu
    $('#accordion .panel-collapse').first().addClass('in');

    // activate first tab
    $('.subpart-nav li').first().addClass('active');
    $('.subpart .tab-content .tab-pane').first().addClass('in active');

    // format datepicker fields
    $('.input-group.date').datepicker({
        todayBtn: "linked",
        clearBtn: true,
        language: "de",
        autoclose: true
    });

});

$(document).ready(function() {
    $('[data-toggle=offcanvas]').click(function() {
        $('.row-offcanvas').toggleClass('active');
    });
});

