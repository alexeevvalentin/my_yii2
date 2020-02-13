<?php

$newstr = $this->base_id.'_new_str';
$newstb = $this->base_id.'_new_stb';
$delstr = $this->base_id.'_del_str';
$delstb = $this->base_id.'_del_stb';

$cur_id_graggable = $this->base_id.'_draggable';
$cur_id_bg_dialog = $this->base_id.'_background_dialog';

$cur_jquery_code = <<<JS

$("#$cur_id_graggable").prepend('<div id="$cur_id_bg_dialog" style="cursor:move;width:100%;height:100%;z-index:0;position:fixed;top:0px;left:0px;background-color:#0F2E5D;opacity:0.5;"></div>');
$("#$cur_id_graggable").prepend('<div id="$this->base_id'+'_data_dialog" style="display:none;"></div>');

$("#$this->base_id").on("past_show_"+"$this->base_id", function(){
    
    var selector_gb = $("#$cur_id_graggable");
    var selector_base_id = $("#$this->base_id");
    var cur_left = (1*$(window).width()-1*selector_base_id.width())/2 - 1*selector_gb.position().left;
    var cur_top = (1*$(window).height()-1*selector_base_id.height())/2 - 1*selector_gb.position().top;
    $("#$this->base_id").css('left', cur_left);
    $("#$this->base_id").css('top', cur_top);

});



$("#$this->base_id").on("pre_show_"+"$this->base_id", function(){
    
    var selector_input_dialog_data = $("#$this->base_id"+"_data_dialog");
    
    var data_source_dialog = $("#$this->base_id"+"_gen_dialog_datasource");
    
    if(selector_input_dialog_data.html() !== ''){
        // ОТОБРАЖЕНИЕ СОХРАНЕННЫХ ДАННЫХ В ДИАЛОГЕ (при открытии диалога формируется каждый раз для необходимости прогрузки данных json вне диалоговых событий, без необходимости формирования html верстки вне диалога)
        var save_json = JSON.parse(selector_input_dialog_data.html());
        var str_table = '<div class="str">';
        var head_tbl = [];
        var head_tbl_rev = [];
        
        var count_head = Object.keys(save_json['head']).length;
        
        for (var i = 0; i < Object.keys(save_json['head']).length; i++){
            head_tbl[i] = Object.keys(save_json['head'])[i];
            head_tbl_rev[Object.keys(save_json['head'])[i]] = i;
            str_table = str_table + '<div class="stb" style="display:table-cell;"><input style="background-color:#75AAF9;" placeholder="Название столбца" type="text" value="'+save_json['head'][Object.keys(save_json['head'])[i]]+'"/></div>';
        }
        str_table = str_table+'</div>';
        data_source_dialog.append(str_table);
        
        str_table = str_table.replace(/background-color:#75AAF9;/g,"");
        str_table = str_table.replace(/placeholder="Название столбца"/g,"");
        
        for (var i = 0; i < (Object.keys(save_json['data']).length/count_head); i++){
            var selector_str_table = $(str_table);
            for (var j = 0; j < Object.keys(head_tbl).length; j++){
                selector_str_table.find('div[class="stb"]').eq(j).find('input').attr('value', save_json['data'][head_tbl[j]+'_'+(i+1)]);
            }
            var prom_str_table = selector_str_table.get(0);
            $("#$this->base_id"+"_gen_dialog_datasource").append(prom_str_table);
        }
    }
    
});

var this_gendialog = $("#$this->base_id"+"_gen_dialog_datasource")

this_gendialog.css('overflow', 'auto');
this_gendialog.css('min-height', '30px');
this_gendialog.css('max-height', '430px');
this_gendialog.css('min-width', '30px');
this_gendialog.css('max-width', '800px');
this_gendialog.css('display', 'block');
this_gendialog.css('margin', '3px');

$("#$this->base_id"+"_gen_dialog_datasource").css('background-color', 'grey');

$(".$this->base_id"+"_new_str").click(function(){
//$(document).on("click", ".$this->base_id"+"_new_str", function(){ 
    var data_source_dialog = $("#$this->base_id"+"_gen_dialog_datasource");
    if(data_source_dialog.find("div[class='str']").last().length === 0){
        data_source_dialog.append('<div class="str"><div class="stb" style="display:table-cell;"><input style="background-color:#75AAF9;" placeholder="Название столбца" type="text" /></div></div>');
    }else{
        var new_str = '<div class="str">';
        if(data_source_dialog.find("div[class='str']").last().index() !== -1){
            new_str = new_str + data_source_dialog.find("div[class='str']").last().html() + '</div>';
            var selector_prom_new_str = $(new_str);
            selector_prom_new_str.find('div[class="stb"]').each(function (i, el){
                $(el).find('input').val('');
                $(el).find('input').css("background-color", "");
                $(el).find('input').removeAttr("placeholder");
            });
            var string_prom_new_str = selector_prom_new_str.get(0);
            data_source_dialog.append(string_prom_new_str);
        }else{
            new_str = new_str + data_source_dialog.find("div[class='str']").last().html();
            new_str = new_str + '</div>';
            data_source_dialog.append(new_str);
        }
    }
});

$(".$this->base_id"+"_new_stb").click(function(){
//$(document).on("click", ".$this->base_id"+"_new_str", function(){ 
    var data_source_dialog = $("#$this->base_id"+"_gen_dialog_datasource");
    if(data_source_dialog.find("div[class='stb']").last().length === 0){
        data_source_dialog.append('<div class="str"><div class="stb" style="display:table-cell;"><input style="background-color:#75AAF9;" placeholder="Название столбца" type="text" /></div></div>');
    }else{
        data_source_dialog.find("div[class='str']").each(function (i, el){
            if(i==0){
                $(el).append('<div class="stb" style="display:table-cell;"><input style="background-color:#75AAF9;" placeholder="Название столбца" type="text" /></div>');
            }else{
                $(el).append('<div class="stb" style="display:table-cell;"><input type="text" /></div>');
            }
        });
    }
    
});

$(".$this->base_id"+"_del_str").click(function(){
    var data_source_dialog = $("#$this->base_id"+"_gen_dialog_datasource");
    if(data_source_dialog.find("div[class='str']").last().length !== 0){
        data_source_dialog.find("div[class='str']").last().remove();
    }			
});

$(".$this->base_id"+"_del_stb").click(function(){
    var data_source_dialog = $("#$this->base_id"+"_gen_dialog_datasource");
    if(data_source_dialog.find("div[class='stb']").last().length !== 0){
        data_source_dialog.find("div[class='str']").each(function (i, el){
            $(el).find("div[class='stb']").last().remove();
        });
    }
});

function transliterate(input){
    var gost = {
        "Є":"YE","І":"I","Ѓ":"G","і":"i","№":"-","є":"ye","ѓ":"g",
        "А":"A","Б":"B","В":"V","Г":"G","Д":"D",
        "Е":"E","Ё":"YO","Ж":"ZH",
        "З":"Z","И":"I","Й":"J","К":"K","Л":"L",
        "М":"M","Н":"N","О":"O","П":"P","Р":"R",
        "С":"S","Т":"T","У":"U","Ф":"F","Х":"X",
        "Ц":"C","Ч":"CH","Ш":"SH","Щ":"SHH","Ъ":"'",
        "Ы":"Y","Ь":"","Э":"E","Ю":"YU","Я":"YA",
        "а":"a","б":"b","в":"v","г":"g","д":"d",
        "е":"e","ё":"yo","ж":"zh",
        "з":"z","и":"i","й":"j","к":"k","л":"l",
        "м":"m","н":"n","о":"o","п":"p","р":"r",
        "с":"s","т":"t","у":"u","ф":"f","х":"x",
        "ц":"c","ч":"ch","ш":"sh","щ":"shh","ъ":"",
        "ы":"y","ь":"","э":"e","ю":"yu","я":"ya",
        " ":"_","—":"_",",":"_","!":"_","@":"_",
        "#":"-","$":"","%":"","^":"","&":"","*":"",
        "(":"",")":"","+":"","=":"",";":"",":":"",
        "'":"","\"":"","~":"","`":"","?":"","/":"",
        "\\\":"","\[":"","]":"","{":"","}":"","|":""
    };

    for (var i=0; i < input.length; i++){
        if(gost[input[i]] !== undefined){
            input = input.replace(input[i], gost[input[i]]);
        }
    }
    return input;
}

function md5(str){
    var RotateLeft = function(lValue, iShiftBits) {
            return (lValue<<iShiftBits) | (lValue>>>(32-iShiftBits));
        };
    var AddUnsigned = function(lX,lY) {
            var lX4,lY4,lX8,lY8,lResult;
            lX8 = (lX & 0x80000000);
            lY8 = (lY & 0x80000000);
            lX4 = (lX & 0x40000000);
            lY4 = (lY & 0x40000000);
            lResult = (lX & 0x3FFFFFFF)+(lY & 0x3FFFFFFF);
            if (lX4 & lY4) {
                return (lResult ^ 0x80000000 ^ lX8 ^ lY8);
            }
            if (lX4 | lY4) {
                if (lResult & 0x40000000) {
                    return (lResult ^ 0xC0000000 ^ lX8 ^ lY8);
                } else {
                    return (lResult ^ 0x40000000 ^ lX8 ^ lY8);
                }
            } else {
                return (lResult ^ lX8 ^ lY8);
            }
        };
    var F = function(x,y,z) { return (x & y) | ((~x) & z); };
    var G = function(x,y,z) { return (x & z) | (y & (~z)); };
    var H = function(x,y,z) { return (x ^ y ^ z); };
    var I = function(x,y,z) { return (y ^ (x | (~z))); };
    var FF = function(a,b,c,d,x,s,ac) {
            a = AddUnsigned(a, AddUnsigned(AddUnsigned(F(b, c, d), x), ac));
            return AddUnsigned(RotateLeft(a, s), b);
        };
    var GG = function(a,b,c,d,x,s,ac) {
            a = AddUnsigned(a, AddUnsigned(AddUnsigned(G(b, c, d), x), ac));
            return AddUnsigned(RotateLeft(a, s), b);
        };

    var HH = function(a,b,c,d,x,s,ac) {
            a = AddUnsigned(a, AddUnsigned(AddUnsigned(H(b, c, d), x), ac));
            return AddUnsigned(RotateLeft(a, s), b);
        };
    var II = function(a,b,c,d,x,s,ac) {
            a = AddUnsigned(a, AddUnsigned(AddUnsigned(I(b, c, d), x), ac));
            return AddUnsigned(RotateLeft(a, s), b);
        };
    var ConvertToWordArray = function(str) {
            var lWordCount;
            var lMessageLength = str.length;
            var lNumberOfWords_temp1=lMessageLength + 8;
            var lNumberOfWords_temp2=(lNumberOfWords_temp1-(lNumberOfWords_temp1 % 64))/64;
            var lNumberOfWords = (lNumberOfWords_temp2+1)*16;
            var lWordArray=Array(lNumberOfWords-1);
            var lBytePosition = 0;
            var lByteCount = 0;
            while ( lByteCount < lMessageLength ) {
                lWordCount = (lByteCount-(lByteCount % 4))/4;
                lBytePosition = (lByteCount % 4)*8;
                lWordArray[lWordCount] = (lWordArray[lWordCount] | (str.charCodeAt(lByteCount)<<lBytePosition));
                lByteCount++;
            }
            lWordCount = (lByteCount-(lByteCount % 4))/4;
            lBytePosition = (lByteCount % 4)*8;
            lWordArray[lWordCount] = lWordArray[lWordCount] | (0x80<<lBytePosition);
            lWordArray[lNumberOfWords-2] = lMessageLength<<3;
            lWordArray[lNumberOfWords-1] = lMessageLength>>>29;
            return lWordArray;
        };
    var WordToHex = function(lValue) {
            var WordToHexValue="",WordToHexValue_temp="",lByte,lCount;
            for (lCount = 0;lCount<=3;lCount++) {
                lByte = (lValue>>>(lCount*8)) & 255;
                WordToHexValue_temp = "0" + lByte.toString(16);
                WordToHexValue = WordToHexValue + WordToHexValue_temp.substr(WordToHexValue_temp.length-2,2);
            }
            return WordToHexValue;
        };
    var x = [];
    var k,AA,BB,CC,DD,a,b,c,d;
    var S11=7, S12=12, S13=17, S14=22;
    var S21=5, S22=9 , S23=14, S24=20;
    var S31=4, S32=11, S33=16, S34=23;
    var S41=6, S42=10, S43=15, S44=21;
    str = encode_utf8(str);
    x = ConvertToWordArray(str);
    a = 0x67452301; b = 0xEFCDAB89; c = 0x98BADCFE; d = 0x10325476;
    for (k=0;k<x.length;k+=16) {
        AA=a; BB=b; CC=c; DD=d;
        a=FF(a,b,c,d,x[k+0], S11,0xD76AA478);
        d=FF(d,a,b,c,x[k+1], S12,0xE8C7B756);
        c=FF(c,d,a,b,x[k+2], S13,0x242070DB);
        b=FF(b,c,d,a,x[k+3], S14,0xC1BDCEEE);
        a=FF(a,b,c,d,x[k+4], S11,0xF57C0FAF);
        d=FF(d,a,b,c,x[k+5], S12,0x4787C62A);
        c=FF(c,d,a,b,x[k+6], S13,0xA8304613);
        b=FF(b,c,d,a,x[k+7], S14,0xFD469501);
        a=FF(a,b,c,d,x[k+8], S11,0x698098D8);
        d=FF(d,a,b,c,x[k+9], S12,0x8B44F7AF);
        c=FF(c,d,a,b,x[k+10],S13,0xFFFF5BB1);
        b=FF(b,c,d,a,x[k+11],S14,0x895CD7BE);
        a=FF(a,b,c,d,x[k+12],S11,0x6B901122);
        d=FF(d,a,b,c,x[k+13],S12,0xFD987193);
        c=FF(c,d,a,b,x[k+14],S13,0xA679438E);
        b=FF(b,c,d,a,x[k+15],S14,0x49B40821);
        a=GG(a,b,c,d,x[k+1], S21,0xF61E2562);
        d=GG(d,a,b,c,x[k+6], S22,0xC040B340);
        c=GG(c,d,a,b,x[k+11],S23,0x265E5A51);
        b=GG(b,c,d,a,x[k+0], S24,0xE9B6C7AA);
        a=GG(a,b,c,d,x[k+5], S21,0xD62F105D);
        d=GG(d,a,b,c,x[k+10],S22,0x2441453);
        c=GG(c,d,a,b,x[k+15],S23,0xD8A1E681);
        b=GG(b,c,d,a,x[k+4], S24,0xE7D3FBC8);
        a=GG(a,b,c,d,x[k+9], S21,0x21E1CDE6);
        d=GG(d,a,b,c,x[k+14],S22,0xC33707D6);
        c=GG(c,d,a,b,x[k+3], S23,0xF4D50D87);
        b=GG(b,c,d,a,x[k+8], S24,0x455A14ED);
        a=GG(a,b,c,d,x[k+13],S21,0xA9E3E905);
        d=GG(d,a,b,c,x[k+2], S22,0xFCEFA3F8);
        c=GG(c,d,a,b,x[k+7], S23,0x676F02D9);
        b=GG(b,c,d,a,x[k+12],S24,0x8D2A4C8A);
        a=HH(a,b,c,d,x[k+5], S31,0xFFFA3942);
        d=HH(d,a,b,c,x[k+8], S32,0x8771F681);
        c=HH(c,d,a,b,x[k+11],S33,0x6D9D6122);
        b=HH(b,c,d,a,x[k+14],S34,0xFDE5380C);
        a=HH(a,b,c,d,x[k+1], S31,0xA4BEEA44);
        d=HH(d,a,b,c,x[k+4], S32,0x4BDECFA9);
        c=HH(c,d,a,b,x[k+7], S33,0xF6BB4B60);
        b=HH(b,c,d,a,x[k+10],S34,0xBEBFBC70);
        a=HH(a,b,c,d,x[k+13],S31,0x289B7EC6);
        d=HH(d,a,b,c,x[k+0], S32,0xEAA127FA);
        c=HH(c,d,a,b,x[k+3], S33,0xD4EF3085);
        b=HH(b,c,d,a,x[k+6], S34,0x4881D05);
        a=HH(a,b,c,d,x[k+9], S31,0xD9D4D039);
        d=HH(d,a,b,c,x[k+12],S32,0xE6DB99E5);
        c=HH(c,d,a,b,x[k+15],S33,0x1FA27CF8);
        b=HH(b,c,d,a,x[k+2], S34,0xC4AC5665);
        a=II(a,b,c,d,x[k+0], S41,0xF4292244);
        d=II(d,a,b,c,x[k+7], S42,0x432AFF97);
        c=II(c,d,a,b,x[k+14],S43,0xAB9423A7);
        b=II(b,c,d,a,x[k+5], S44,0xFC93A039);
        a=II(a,b,c,d,x[k+12],S41,0x655B59C3);
        d=II(d,a,b,c,x[k+3], S42,0x8F0CCC92);
        c=II(c,d,a,b,x[k+10],S43,0xFFEFF47D);
        b=II(b,c,d,a,x[k+1], S44,0x85845DD1);
        a=II(a,b,c,d,x[k+8], S41,0x6FA87E4F);
        d=II(d,a,b,c,x[k+15],S42,0xFE2CE6E0);
        c=II(c,d,a,b,x[k+6], S43,0xA3014314);
        b=II(b,c,d,a,x[k+13],S44,0x4E0811A1);
        a=II(a,b,c,d,x[k+4], S41,0xF7537E82);
        d=II(d,a,b,c,x[k+11],S42,0xBD3AF235);
        c=II(c,d,a,b,x[k+2], S43,0x2AD7D2BB);
        b=II(b,c,d,a,x[k+9], S44, 0xEB86D391);
        a=AddUnsigned(a,AA);
        b=AddUnsigned(b,BB);
        c=AddUnsigned(c,CC);
        d=AddUnsigned(d,DD);
    }
    var temp = WordToHex(a)+WordToHex(b)+WordToHex(c)+WordToHex(d);
    return temp.toLowerCase();
}

function encode_utf8(s) {
  return unescape(encodeURIComponent(s));
}

function decode_utf8(s) {
  return decodeURIComponent(escape(s));
}

$("#$this->base_id"+"_rec_dialog_datasource").click(function(){
    var data_source_dialog = $("#$this->base_id"+"_gen_dialog_datasource");
    var selector_input_dialog_data = $("#$this->base_id"+"_data_dialog");
    var cur_string = '';
    var cur_md5 = '';
    var json_arr = {};
    var fl_break = 0;
    var fl_data = 0;
    json_arr['head'] = {};
    json_arr['data'] = {};
    json_arr['color'] = {};
    data_source_dialog.find("div[class='str']").each(function (i, el){
        $(el).find("div[class='stb']").each(function (j, elj){
            if(i==0){
                cur_string = ''+$(elj).find('input').val();
                json_arr['head'][transliterate(cur_string)] = cur_string;
                cur_md5 = md5("color"+cur_string);
                json_arr['color'][transliterate(cur_string)] = parseInt(cur_md5.substr(0,2), 16)+','+parseInt(cur_md5.substr(2,2), 16)+','+parseInt(cur_md5.substr(4,2), 16);
            }else{
                fl_data = 1;
                cur_string = ''+$(elj).find('input').val();
                if(Object.keys(json_arr['head'])[j] === undefined){
                    fl_break = 1;
                }
                json_arr['data'][Object.keys(json_arr['head'])[j]+'_'+i] = cur_string;
            }
        });
    });
    if (fl_break === 0){
        if(fl_data == 1){
            selector_input_dialog_data.html(JSON.stringify(json_arr));
            $("#"+"$this->base_id").trigger("$this->base_id"+"_rec_dialog_complete", [json_arr]);
            data_source_dialog.html('');
            $("#"+"$this->base_id"+"_controll_btn_x_close").click();
            
            //$('#'+uniq_key_id_prefix+'ds_canvas_select').val('');
            //$('#'+uniq_key_id_prefix+'gen_canvas').trigger('build_json_data', [json_arr, $('#'+uniq_key_id_prefix+'gen_canvas')]);
        } else {
            alert('Необходимо заполнить данные!');
        }
    } else {
        alert('Необходимо ввести уникальные названия столбцов !');
    }
    
});

$("#$this->base_id").on("pre_x_close_"+"$this->base_id", function(){
    var data_source_dialog = $("#$this->base_id"+"_gen_dialog_datasource");
    data_source_dialog.html('');
});

JS;

return $cur_jquery_code;

?>