cp_spacer_image=ko_path+"images/clear.gif";cp_hexValues=Array("0","1","2","3","4","5","6","7","8","9","a","b","c","d","e","f");cp_currentColor="";cp_parentInputElement=null;function getAbsolutePos(g){var a=0,f=0;var e=/^div$/i.test(g.tagName);if(e&&g.scrollLeft){a=g.scrollLeft}if(e&&g.scrollTop){f=g.scrollTop}var h={x:g.offsetLeft-a,y:g.offsetTop-f};if(g.offsetParent){var b=getAbsolutePos(g.offsetParent);h.x+=b.x;h.y+=b.y}return h}document.writeln('<style type="text/css">');document.writeln("#cp_colorpickerbox {position: absolute; top: 0px; left: 0px; visibility: hidden; border: 1px solid #000000; padding: 10px; \\width: 313px; w\\idth: 291px; \\height: 185px; he\\ight: 165px; background-color: #FFFFFF;}");document.writeln("#cp_colorbox {position: absolute; overflow: hidden; \\border: 0px; b\\order: 1px solid #000000; top: 35px; left: 160px; font-size: 40px;}");document.writeln("#cp_colorbox img {border: 0px; width: 128px; height: 14px;}");document.writeln("#cp_colorbox span {font-size: 15px; width: 128px; height: 14px;}");document.writeln("#cp_spectrumbox {position: absolute; overflow: hidden; \\border: 0px; b\\order: 1px solid #000000; font-size: 40px;}");document.writeln("#cp_spectrumbox img {border: 0px; width: 3px; height: 15px;}");document.writeln("#cp_satbrightbox {position: absolute; overflow: hidden; \\border: 0px; b\\order: 1px solid #000000; top: 35px; left: 10px;}");document.writeln("#cp_satbrightbox img {border: 0px; width: 8px; height: 8px;}");document.writeln("#cp_hexvaluebox {position: absolute; border: 0px solid #000000; top: 70px; left: 160px; font-size: 15px;}");document.writeln("#cp_hexvaluebox input {border: 1px solid #000000; width: 100px; font-size: 15px;}");document.writeln("#cp_controlbox {position: absolute; border: 0px solid #000000; top: 120px;left: 210px;}");document.writeln("#cp_controlbox input {border: 1px solid #000000; width: 78px; height: 18px; font-size: 10px; background-color: #FFFFFF;}");document.writeln("</style>");document.writeln('<div id="cp_colorpickerbox">');document.writeln('    <div id="cp_spectrumbox">&nbsp;</div>');document.writeln('    <div id="cp_satbrightbox">&nbsp;</div>');document.writeln('    <div id="cp_colorbox"><img src="'+ko_path+'images/clear.gif" /></div>');document.writeln('    <div id="cp_hexvaluebox">#<input type="text" value="" /></div>');document.writeln('    <div id="cp_controlbox"><input type="submit" value="OK" onclick="cp_ok();" /><br /><br /><input type="submit" value="Cancel" onclick="cp_hide();" /></div>');document.writeln("</div>");function cp_showSatBrightBox(a){element=cp_getElementById("cp_satbrightbox");html="";s=16;colEnd=Array();a[0]=256-a[0];a[1]=256-a[1];a[2]=256-a[2];for(j=0;j<3;j++){colEnd[j]=Array();for(i=s;i>-1;i--){colEnd[j][i]=i*Math.round(a[j]/s)}}hexStr="";for(k=s;k>-1;k--){for(i=s;i>-1;i--){for(j=0;j<3;j++){dif=256-colEnd[j][k];quot=(dif!=0)?Math.round(dif/s):0;hexStr+=cp_toHex(i*quot)}html+='<span style="background-color:#'+hexStr+';"><a href="#" onclick="cp_showColorBox(\''+hexStr+'\');"><img src="'+cp_spacer_image+'"/></a></span>';hexStr=""}html+="<br />"}element.innerHTML=html}function cp_showSpectrumBox(){element=cp_getElementById("cp_spectrumbox");html="";d=1;c=0;v=0;s=16;col=Array(256,0,0);ind=1;cel=256;while(c<(6*256)){html+='<span style="background-color:#'+cp_toHex(col[0])+cp_toHex(col[1])+cp_toHex(col[2])+'"><a href="#" onclick="cp_showSatBrightBox(Array('+col[0]+","+col[1]+","+col[2]+'));"><img src="'+cp_spacer_image+'" /></a></span>';c+=s;v+=(s*d);col[ind]=v;if(v==cel){ind-=1;if(ind==-1){ind=2}d=d*-1}if(v==0){ind+=2;if(ind==3){ind=0}d=d*-1}}element.innerHTML=html;cp_showSatBrightBox(col)}function cp_toHex(a){if(a>0){a-=1}base=a/16;rem=a%16;base=base-(rem/16);return cp_hexValues[base]+cp_hexValues[rem]}function cp_showColorBox(a){colorbox=cp_getElementById("cp_colorbox");colorboxhtml='<span style="background-color:#'+a+'"><img src="'+cp_spacer_image+'" /></span>';colorbox.innerHTML=colorboxhtml;hexvaluebox=cp_getElementById("cp_hexvaluebox");hexvalueboxhtml='#<input type="text" value="'+a+'" />';hexvaluebox.innerHTML=hexvalueboxhtml;cp_currentColor=a}function cp_show(a){cp_showSpectrumBox();p=getAbsolutePos(a);p.y+=20;element=cp_getElementById("cp_colorpickerbox");element.style.left=p.x+"px";element.style.top=p.y+"px";element.style.visibility="visible";cp_parentInputElement=a}function cp_ok(){cp_parentInputElement.value="#"+cp_currentColor;cp_parentInputElement.style.background="#"+cp_currentColor;cp_hide()}function cp_hide(){element=cp_getElementById("cp_colorpickerbox");element.style.visibility="hidden"}function cp_getElementById(b,a){if(document.getElementById){return document.getElementById(b)}if(document.all){return document.all[b]}};