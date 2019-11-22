<?php
namespace app\models;

use Yii;
use yii\base\Model;
use app\models\GenF;
use yii\helpers\Url;
use yii\helpers\Json;

/**
* This is the model class for table "books".
*
* @property int $id
* @property string $name
* @property string $author
* @property string $edition
* @property string $year
*/
class tag extends Model
{

    private static function arr_sc(){
        $specialChars = [
        '!', '"', '#', '$', '%', '&', "'", '(', ')', '*', '+',
        ',', '/', ':', ';', '<', '=', '>', '?', '@', '[', '\\',
        ']', '^', '_', '`', '{', '|', '}', '§', '©', '¶'
        ];
        return $specialChars;
    }

    private static function to_htmlspecialchars($str){
        if(preg_match('/[\'\/~`\!@#\$%\^&\*\(\)_\-\+=\{\}\[\]\|;:"\<\>,\.\?\\\]/', $str)){
            if(GenF::index_of($str,'&') !== -1){
                return $str;
            }else{
                $str = htmlspecialchars($str);
                return self::to_htmlspecialchars($str);
            }
        }else{
            return $str;
        }
    }

    public static function tag_symb($str, $replace='_'){
        $str_new = preg_replace("/&#?[a-z0-9]+;/i", $replace, $str);
        $str_new = preg_replace("/(?![.=$'€%-])\p{P}/u", $replace, $str_new);
        $str_new = preg_replace('| +|', $replace, $str_new);
        $str_new = str_replace(".", $replace, $str_new);
        $str_new = trim($str_new);
        return $str_new;
    }

    public static function select_monitor($model, $id_commun, $name, $options='', $set_value=null){
        $this_id = self::tag_symb($name);
        $class_name = end(explode("\\", get_class($model)));

        if($options === ''){
            $options = 'style="width:100%;height:150px;overflow:auto;background-color:#E4E4E4;border: 3px solid #A5A5A5;border-radius:8px;"';
        }

        if(is_array($options)){
            $str_options = '';
            foreach($options as $k=>$v){
                $str_options = $str_options.$k.'="'.$v.'" ';
            }
            $options = $str_options;
        }
        if($set_value === null){
            if(isset($model->$name)){
                $set_value = $model->$name;
            }
        }else if(is_array($set_value)){
            foreach($set_value as $k=>$v){
                if(is_string($v)){
                    $set_value[$k] = htmlspecialchars($v);
                }
            }
            $set_value = Json::encode($set_value);
        }

        if($set_value == ''){
            $set_value = Json::encode([]);
        }

        $tag_source = '<div id="'.$this_id.'" name="'.$class_name.'['.$name.']" '.$options.' value=\''.htmlspecialchars($set_value).'\'><input type="hidden" id="'.$this_id.'_hide" name="'.$class_name.'['.$name.'_hide]" /></div>';
        echo $tag_source;

        $jsTag =<<<JS

            var this_id_key = "$this_id";
            var this_id_hide = this_id_key+"_hide";
            var class_del_button = "del_"+this_id_key;

            var sel_commun = $("#$id_commun");
            var sel_this = $("#$this_id");
            
            $("#"+this_id_hide).val(sel_this.attr("value"));

            var jsonString = '$set_value';
            
            var arr_value = JSON.parse(jsonString);
            
            var source_this = "";
            
            for(var key in arr_value){
                source_this = source_this + "<div style='display:table;' class='"+class_del_button+"_str' id='"+class_del_button+"_"+key+"' ><div style='display:table-cell;padding:3px;'>"+arr_value[key]+"</div><div class='"+class_del_button+"' code='"+key+"' style='display:table-cell;padding:3px;cursor:pointer;'>&#9746;</div> </div>";
            }
            sel_this.append(source_this);

            $(document).on("click", "."+class_del_button, function(){
                class_del_button = "del_"+"$this_id";
                sel_this = $("#$this_id");
                this_id_hide = "$this_id"+"_hide";
                
                var code_val = $(this).attr("code");
                $("#"+class_del_button+"_"+code_val).remove();
                var runtime_val = JSON.parse(sel_this.attr("value"));
                runtime_val = Object.assign({}, runtime_val);
                delete runtime_val[code_val];
                sel_this.attr("value", JSON.stringify(runtime_val));
                $("#"+this_id_hide).val(JSON.stringify(runtime_val));
            });
            
            sel_commun.change(function(){
                var data_commun = JSON.parse($(this).attr("data"));
                var this_code = $(this).attr("code");
                class_del_button = "del_"+"$this_id";
                sel_this = $("#$this_id");
                this_id_hide = "$this_id"+"_hide";
                
                if($("#"+class_del_button+"_"+this_code).length === 0 && this_code !== '' && this_code !== undefined){
                    sel_this.append("<div style='display:table;' class='"+class_del_button+"_str' id='"+class_del_button+"_"+this_code+"' ><div style='display:table-cell;padding:3px;'>"+data_commun[this_code]+"</div><div class='"+class_del_button+"' code='"+this_code+"' style='display:table-cell;padding:3px;cursor:pointer;'>&#9746;</div></div>");
                    var runtime_val = JSON.parse(sel_this.attr("value"));
                    runtime_val = Object.assign({}, runtime_val);
                    runtime_val[''+this_code] = data_commun[this_code];
                    sel_this.attr("value", JSON.stringify(runtime_val));
                    $("#"+this_id_hide).val(JSON.stringify(runtime_val));
                }else{
                    return false;
                }
            });
            
JS;
        Yii::$app->view->registerJs($jsTag);
    }

    public static function input($model, $name, $options='', $set_value=null){
        $this_id = self::tag_symb($name);
        $class_name = end(explode("\\", get_class($model)));

        if(is_array($options)){
            $str_options = '';
            foreach($options as $k=>$v){
                $str_options = $str_options.$k.'="'.$v.'" ';
            }
            $options = $str_options;
        }
        if($set_value === null){
            if(isset($model->$name)){
                $set_value = $model->$name;
            }
        }

        $tag_source = '<input type="text" id="'.$this_id.'" name="'.$class_name.'['.$name.']" '.$options.' value="'.$set_value.'" />';

        echo $tag_source;

    }

    public static function ecocombo($model, $name, $data='', $options='', $set_value=null, $search_case="i"){

        if(is_array($data) || is_object($data)){
            foreach($data as $k=>$v){
                if(is_string($v)){
                    $data[$k] = htmlspecialchars($v);
                }
            }
            $data = Json::encode($data);
        }

        if($data == ''){
            $data = Json::encode([]);
        }

        $this_id = self::tag_symb($name);
        if(is_string($model)){
            $class_name = $model;
        }else{
            $class_name = end(explode("\\", get_class($model)));
        }

        if(is_array($options)){
            $str_options = '';
            foreach($options as $k=>$v){
                $str_options = $str_options.$k.'="'.$v.'" ';
            }
            $options = $str_options;
        }
        if($set_value === null){
            if(isset($model->$name)){
                $set_value = $model->$name;
            }
        }

        $tag_source = '<input type="text" id="'.$this_id.'" name="'.$class_name.'['.$name.']" '.$options.' data=\''.htmlspecialchars($data).'\' value="'.$set_value.'" />';

        echo $tag_source;

        $jsTag =<<<JS

        var selector_this_id = $("#$this_id");
        ecocombo(selector_this_id, "$search_case");
        
        function ecocombo(selector, s_case, in_col = 8){
            
            var source;
            var O_K_source;
            
            if(selector.attr('data') === '' || selector.attr('data') === undefined){}else{
                source = JSON.parse(selector.attr('data'));
                source = arr_sorti_local(source);
                O_K_source = Object.keys(source);
            }

            var this_id = selector.attr("id")+"_btn";
            selector.parent().append('<div id="'+this_id+'" class="ecocombo_btn" style="position:relative;left:'+(selector.width()-16)+'px;top:-24px;width:18px;height:16px;font-size:9px;cursor:pointer;text-align:center;padding-top:5px;">&#9660;</div>');
            var this_selector = $('#'+this_id);

            function arr_sorti_local(arr, sort_stb = 0){
                var sortable = [];
                for (var vehicle in arr) {sortable.push([1*vehicle, vehicle, arr[vehicle]]);}
                sortable.sort(function(a, b) {return a[sort_stb] - b[sort_stb]});
                return sortable;
            }
                
            function combo_step_scroll(clist, cbutton, cdata, fl){
                var diap_s = cbutton.attr('diap_s');
                var diap_e = cbutton.attr('diap_e');
                if(typeof diap_s !== typeof undefined && diap_s !== false && typeof diap_e !== typeof undefined && diap_e !== false){
                    if(fl === 1){
                        diap_s = 1*cbutton.attr('diap_s') + 1;
                        diap_e = 1*cbutton.attr('diap_e') + 1;

                        if(O_K_source[diap_e] === undefined){
                            return false;
                        }

                        /*
                        if(Object.keys(cdata)[diap_e] === undefined){
                            return false;
                        }
                        */
                        
                        cbutton.attr('diap_s', diap_s);
                        cbutton.attr('diap_e', diap_e);
                        clist.html('');
                        list_id = clist.attr('id');

                        for(var i = diap_s; i < 1*diap_e; i++){
                            if(O_K_source[i] !== undefined){
                                clist.append('<div class="'+list_id+'_option_row" code="'+cdata[O_K_source[i]][1]+'" onmouseover="this.style.backgroundColor=&quot;#DCDCDC&quot;;" onmouseout="this.style.backgroundColor=&quot;#FFFFFF&quot;;" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><div style="display:table-cell;padding:3px;font-weight:bold;">'+cdata[O_K_source[i]][1]+'</div><div style="display:table-cell;padding:3px;text-overflow:ellipsis;">'+cdata[O_K_source[i]][2]+'</div></div>');
                            }
                        }
                        
                        /*
                        for(var i = diap_s; i < 1*diap_e; i++){
                            if(Object.keys(cdata)[i] !== undefined){
                                clist.append('<div class="'+list_id+'_option_row" code="'+cdata[Object.keys(cdata)[i]][1]+'" onmouseover="this.style.backgroundColor=&quot;#DCDCDC&quot;;" onmouseout="this.style.backgroundColor=&quot;#FFFFFF&quot;;" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><div style="display:table-cell;padding:3px;font-weight:bold;">'+cdata[Object.keys(cdata)[i]][1]+'</div><div style="display:table-cell;padding:3px;text-overflow:ellipsis;">'+cdata[Object.keys(cdata)[i]][2]+'</div></div>');
                            }
                        }
                        */
                    }else if(fl === 2){
                        diap_s = 1*cbutton.attr('diap_s') - 1;
                        diap_e = 1*cbutton.attr('diap_e') - 1;
                        if(diap_s < 0){
                            return false;
                        }
                        cbutton.attr('diap_s', diap_s);
                        cbutton.attr('diap_e', diap_e);
                        clist.html('');
                        list_id = clist.attr('id');

                        for(var i = diap_s; i < 1*diap_e; i++){
                            if(O_K_source[i] !== undefined){
                                clist.append('<div class="'+list_id+'_option_row" code="'+cdata[O_K_source[i]][1]+'" onmouseover="this.style.backgroundColor=&quot;#DCDCDC&quot;;" onmouseout="this.style.backgroundColor=&quot;#FFFFFF&quot;;" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><div style="display:table-cell;padding:3px;font-weight:bold;">'+cdata[O_K_source[i]][1]+'</div><div style="display:table-cell;padding:3px;text-overflow:ellipsis;">'+cdata[O_K_source[i]][2]+'</div></div>');
                            }
                        }

                        /*
                        for(var i = diap_s; i < 1*diap_e; i++){
                            if(Object.keys(cdata)[i] !== undefined){
                                clist.append('<div class="'+list_id+'_option_row" code="'+cdata[Object.keys(cdata)[i]][1]+'" onmouseover="this.style.backgroundColor=&quot;#DCDCDC&quot;;" onmouseout="this.style.backgroundColor=&quot;#FFFFFF&quot;;" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><div style="display:table-cell;padding:3px;font-weight:bold;">'+cdata[Object.keys(cdata)[i]][1]+'</div><div style="display:table-cell;padding:3px;text-overflow:ellipsis;">'+cdata[Object.keys(cdata)[i]][2]+'</div></div>');
                            }
                        }
                        */
                    }
                }
            }
            
            function recurs_data_list_i(data_text, cur_value, final_text, step=0){
                
                step = step + 1;
                
                //datatextIOCV = data_text.indexOf(cur_value);
                //datatextIOCV2 = data_text.indexOf('"'+cur_value+'"');
                
                if(cur_value === ','){if(1*data_text.search(new RegExp(cur_value, "i")) === (1 + 1*data_text.search(new RegExp('"'+cur_value+'"', "i")))){return false;}}
                else if(cur_value === '"'){return false;}
                else if(cur_value === ''){return false;}
                else if(cur_value === '{'){return false;}
                else if(cur_value === '}'){return false;}
                else if(cur_value === ':'){if(1*data_text.search(new RegExp(cur_value, "i")) === (1 + 1*data_text.search(new RegExp('"'+cur_value+'"', "i")))){return false;}}

                if(data_text.search(new RegExp(cur_value, "i")) !== -1){
                    // больше 1000 в селекте нет смысла отображать для поиска
                    if(step < 1000){
                        var prom_data_begin = data_text.substr(0, data_text.search(new RegExp(cur_value, "i")));
                        if(prom_data_begin.lastIndexOf('","') !== -1){var index_begin = prom_data_begin.lastIndexOf('","') + ('","').length;}
                        else{if(prom_data_begin.indexOf('{') === 0){var index_begin = 0;if(final_text === undefined){final_text = '';}}}
                        if(final_text === undefined){final_text = '{"';}
                        var prom_data_end = data_text.substr(data_text.search(new RegExp(cur_value, "i")));
                        var prom_data_end_delim = prom_data_end.indexOf('","');
                        if(prom_data_end_delim === -1){prom_data_end_delim = prom_data_end.indexOf('}');}
                        var index_end = 1*(data_text.search(new RegExp(cur_value, "i"))) + 1*prom_data_end_delim;
                        final_text = final_text + data_text.substr(1*index_begin , (1*(data_text.search(new RegExp(cur_value, "i"))) - 1*index_begin) + 1*prom_data_end_delim)+'","';
                        data_text = data_text.substr(1*index_begin + (1*(data_text.search(new RegExp(cur_value, "i"))) - 1*index_begin) + 1*prom_data_end_delim);
                        return recurs_data_list_i(data_text, cur_value, final_text, step);
                    }else{
                        if(final_text !== undefined){
                            if(final_text.indexOf('","') !== -1){
                                if(final_text.indexOf('"","')!==-1){
                                    if(final_text.length === (final_text.indexOf('"","')+('"","').length)){
                                        final_text = final_text.substr(0, final_text.length - ('","').length)+'}';
                                    }else{
                                        final_text = final_text.substr(0, final_text.length - ('","').length)+'"}';
                                    }
                                }else{final_text = final_text.substr(0, final_text.length - ('","').length)+'"}';}
                                return final_text;
                            }else{return false;}
                        }else{return false;}
                    }
                }else{
                    if(final_text !== undefined){
                        if(final_text.indexOf('","') !== -1){
                            if(final_text.indexOf('"","')!==-1){
                                if(final_text.length === (final_text.indexOf('"","')+('"","').length)){
                                    final_text = final_text.substr(0, final_text.length - ('","').length)+'}';
                                }else{
                                    final_text = final_text.substr(0, final_text.length - ('","').length)+'"}';
                                }
                            }else{final_text = final_text.substr(0, final_text.length - ('","').length)+'"}';}
                            return final_text;
                        }else{return false;}
                    }else{return false;}
                }
            }
    
            function recurs_data_list(data_text, cur_value, final_text, step=0){
                
                step = step + 1;
                
                if(cur_value === ','){if(1*data_text.indexOf(cur_value) === (1 + 1*data_text.indexOf('"'+cur_value+'"'))){return false;}}
                else if(cur_value === '"'){return false;}
                else if(cur_value === ''){return false;}
                else if(cur_value === '{'){return false;}
                else if(cur_value === '}'){return false;}
                else if(cur_value === ':'){if(1*data_text.indexOf(cur_value) === (1 + 1*data_text.indexOf('"'+cur_value+'"'))){return false;}}

                if(data_text.indexOf(cur_value) !== -1){
                    // больше 1000 в селекте нет смысла отображать для поиска
                    if(step < 1000){
                        var prom_data_begin = data_text.substr(0, data_text.indexOf(cur_value));
                        if(prom_data_begin.lastIndexOf('","') !== -1){var index_begin = prom_data_begin.lastIndexOf('","') + ('","').length;}
                        else{if(prom_data_begin.indexOf('{') === 0){var index_begin = 0;if(final_text === undefined){final_text = '';}}}
                        if(final_text === undefined){final_text = '{"';}
                        var prom_data_end = data_text.substr(data_text.indexOf(cur_value));
                        var prom_data_end_delim = prom_data_end.indexOf('","');
                        if(prom_data_end_delim === -1){prom_data_end_delim = prom_data_end.indexOf('}');}
                        var index_end = 1*(data_text.indexOf(cur_value)) + 1*prom_data_end_delim;
                        final_text = final_text + data_text.substr(1*index_begin , (1*(data_text.indexOf(cur_value)) - 1*index_begin) + 1*prom_data_end_delim)+'","';
                        data_text = data_text.substr(1*index_begin + (1*(data_text.indexOf(cur_value)) - 1*index_begin) + 1*prom_data_end_delim);
                        return recurs_data_list(data_text, cur_value, final_text, step);
                    }else{
                        if(final_text !== undefined){
                            if(final_text.indexOf('","') !== -1){
                                if(final_text.indexOf('"","')!==-1){
                                    if(final_text.length === (final_text.indexOf('"","')+('"","').length)){
                                        final_text = final_text.substr(0, final_text.length - ('","').length)+'}';
                                    }else{
                                        final_text = final_text.substr(0, final_text.length - ('","').length)+'"}';
                                    }
                                }else{final_text = final_text.substr(0, final_text.length - ('","').length)+'"}';}
                                return final_text;
                            }else{return false;}
                        }else{return false;}
                    }
                }else{
                    if(final_text !== undefined){
                        if(final_text.indexOf('","') !== -1){
                            if(final_text.indexOf('"","')!==-1){
                                if(final_text.length === (final_text.indexOf('"","')+('"","').length)){
                                    final_text = final_text.substr(0, final_text.length - ('","').length)+'}';
                                }else{
                                    final_text = final_text.substr(0, final_text.length - ('","').length)+'"}';
                                }
                            }else{final_text = final_text.substr(0, final_text.length - ('","').length)+'"}';}
                            return final_text;
                        }else{return false;}
                    }else{return false;}
                }
            }

            selector.keyup(function(){
                $(this).attr('code', '');
                $(this).attr('value', '');
                if($('#'+selector.attr('id')+'_list').length > 0){ $('#'+selector.attr('id')+'_list').remove(); }
                var data_text = selector.attr('data');
                if(data_text === undefined){return false;}
                var data_text_clear = '';
                var cur_value = $(this).val();
                
                if(s_case === 'i'){
                    data_text_clear = recurs_data_list_i(data_text, cur_value, undefined);
                }else{
                    data_text_clear = recurs_data_list(data_text, cur_value, undefined);
                }

                if(data_text_clear === false){return false;}
    
                var source = JSON.parse(data_text_clear);
                source = arr_sorti_local(source);
                
                var list_id = selector.attr('id')+'_list';
                var list_html = '<div id="'+list_id+'" style="top:'+ (selector.position().top + selector.height() + 6) +'px;left:'+ selector.position().left +'px;overflow-y:scroll;position:absolute;background-color:white;padding:3px;cursor:default;width:'+(selector.width() + 5)+'px;max-height:192px;"></div>';
                selector.parent().append(list_html);
                var list_selector = $('#'+list_id);
                list_selector.mouseleave(function(){ $(this).remove(); });
                var fl_hover_list = 0;var fl_hover_btn = 0;var fl_hover_genselector = 0;
                this_selector.unbind('mouseenter').unbind('mouseleave');
                this_selector.hover(
                    function() {
                        fl_hover_btn = 1;
                    }, function() {
                        fl_hover_btn = 0;
                        setTimeout(function(){if(fl_hover_list !== 1 && fl_hover_genselector !== 1){list_selector.remove();}}, 0);
                    }
                );
                list_selector.hover(
                    function(){
                        fl_hover_list = 1;
                    },
                    function(){
                        fl_hover_list = 0;
                    }
                );
                $(this).hover(
                    function(){
                        fl_hover_genselector = 1;
                    },
                    function(){
                        fl_hover_genselector = 0;
                        setTimeout(function(){if(fl_hover_list !== 1 && fl_hover_btn !== 1){list_selector.remove();}}, 0);
                    }
                );
    
                for(var i in source){
                    list_selector.append('<div class="'+list_id+'_option_row" code="'+source[O_K_source[i]][1]+'" onmouseover="this.style.backgroundColor=&quot;#DCDCDC&quot;;" onmouseout="this.style.backgroundColor=&quot;#FFFFFF&quot;;" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><div style="display:table-cell;padding:3px;font-weight:bold;">'+source[O_K_source[i]][1]+'</div><div style="display:table-cell;padding:3px;">'+source[O_K_source[i]][2]+'</div></div>');
                }
                
            });
    
            $(document).on("click", "."+selector.attr("id")+"_list_option_row", function(){
    
                selector.attr('code', $(this).attr('code'));
    
                if($(this).children('div').eq(0).html()!==''){
                    selector.val($(this).children('div').eq(0).html()+' - '+$(this).children('div').eq(1).html());
                    selector.attr('value', $(this).attr('code'));
                }else{
                    selector.val($(this).children('div').eq(1).html());
                    selector.attr('value', $(this).attr('code'));
                }
                selector.change();
                if($('#'+selector.attr('id')+'_list').length > 0){
                    $('#'+selector.attr('id')+'_list').remove();
                }
                
            });
            
            this_selector.click(function(){
                
                if( $('#'+selector.attr('id')+'_list').length > 0){ $('#'+selector.attr('id')+'_list').remove();}
                var attr_start = 0;
                var attr_s = $(this).attr('diap_s');
                var attr_end = 1*in_col;
                var attr_e = $(this).attr('diap_e');
                $(this).attr('diap_s', 0);
                $(this).attr('diap_e', in_col);
                if(selector.attr('data') === '' || selector.attr('data') === undefined){return false;}
    
                //var source = JSON.parse(selector.attr('data'));
                //source = arr_sorti_local(source);
    
                var list_id = selector.attr('id')+'_list';
                var list_html = '<div id="'+list_id+'" style="top:'+ (selector.position().top + selector.height() + 6) +'px;left:'+ selector.position().left +'px;overflow-y:scroll;position:absolute;background-color:white;padding:3px;cursor:default;width:'+(selector.width() + 5)+'px;"></div>';
                selector.parent().append(list_html);
                var list_selector = $('#'+list_id);
                list_selector.mouseleave(function(){ $(this).remove();});
                var fl_hover_list = 0;
                var fl_hover_btn = 0;
                var fl_hover_genselector = 0;
    
                $(this).unbind('mouseenter').unbind('mouseleave');
                $(this).hover(
                    function() {
                        fl_hover_btn = 1;
                    }, function() {
                        fl_hover_btn = 0;
                        setTimeout(function(){if(fl_hover_list !== 1 && fl_hover_genselector !== 1){list_selector.remove();}}, 0);
                    }
                );
                list_selector.hover(
                    function(){
                        fl_hover_list = 1;
                    },
                    function(){
                        fl_hover_list = 0;
                    }
                );
                selector.hover(
                    function(){
                        fl_hover_genselector = 1;
                    },
                    function(){
                        fl_hover_genselector = 0;
                        setTimeout(function(){if(fl_hover_list !== 1 && fl_hover_btn !== 1){list_selector.remove();}}, 0);
                    }
                );
    
                var CurrentScroll = 0;
                list_selector.on('DOMMouseScroll mousewheel', function(e){
                    e.preventDefault();
                    if(e.originalEvent.wheelDelta < 0 || e.originalEvent.detail > 0) {
                        //scroll down
                        combo_step_scroll($(this), this_selector, source, 1);
                    } else {
                        //scroll up
                        combo_step_scroll($(this), this_selector, source, 2);
                    }
                });
                
                for(var i = attr_start; i < 1*attr_end; i++){
    
                    if(O_K_source[i] !== undefined){
                        list_selector.append('<div class="'+list_id+'_option_row" code="'+source[O_K_source[i]][1]+'" onmouseover="this.style.backgroundColor=&quot;#DCDCDC&quot;;" onmouseout="this.style.backgroundColor=&quot;#FFFFFF&quot;;" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-height:21px;"><div style="display:table-cell;padding:3px;font-weight:bold;">'+source[O_K_source[i]][1]+'</div><div style="display:table-cell;padding:3px;">'+source[O_K_source[i]][2]+'</div></div>');
                    }else{
                        $(this).attr('diap_e', 1*i);
                        break;
                    }
                    
                    /*
                    if(Object.keys(source)[i] !== undefined){
                        list_selector.append('<div class="'+list_id+'_option_row" code="'+source[Object.keys(source)[i]][1]+'" onmouseover="this.style.backgroundColor=&quot;#DCDCDC&quot;;" onmouseout="this.style.backgroundColor=&quot;#FFFFFF&quot;;" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-height:21px;"><div style="display:table-cell;padding:3px;font-weight:bold;">'+source[Object.keys(source)[i]][1]+'</div><div style="display:table-cell;padding:3px;">'+source[Object.keys(source)[i]][2]+'</div></div>');
                    }else{
                        $(this).attr('diap_e', 1*i);
                        break;
                    }*/
                }
    
            });
        }
JS;

        Yii::$app->view->registerJs($jsTag);

    }

}
?>