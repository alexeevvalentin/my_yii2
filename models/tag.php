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

    public static function select_monitor($model, $id_commun, $name, $data_commun, $options='', $set_value=null){

        $this_id = self::tag_symb($name);

        if(is_array($data_commun) || is_object($data_commun)){
            foreach($data_commun as $k=>$v){
                if(is_string($v)){
                    $data_commun[$k] = htmlspecialchars($v);
                }
            }
            $data_commun = Json::encode($data_commun);
        }

        if(is_string($model)){
            $class_name = $model;
        }else{
            $class_name = end(explode("\\", get_class($model)));
        }

        if($options === ''){
            $options = 'style="width:100%;min-height:150px;overflow:auto;background-color:#E4E4E4;border: 3px solid #A5A5A5;border-radius:8px;"';
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
                if(is_string($model->$name)){
                    $set_value = $model->$name;
                }else if(is_array($model->$name) || is_object($model->$name)){
                    $set_value = Json::encode($model->$name);
                }
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

            function IEArrToObject(arr) {
                var rv = {};
                for (var i = 0; i < arr.length; ++i)
                    rv[i] = arr[i];
                return rv;
            }
            
            $(document).on("click", "."+class_del_button, function(){
                class_del_button = "del_"+"$this_id";
                sel_this = $("#$this_id");
                this_id_hide = "$this_id"+"_hide";
                
                var code_val = $(this).attr("code");
                $("#"+class_del_button+"_"+code_val).remove();
                var runtime_val = JSON.parse(sel_this.attr("value"));
                
                var isIE = false || !!document.documentMode;
                var isEdge = !isIE && !!window.StyleMedia;
                if(isIE === false && isEdge === false){
                    runtime_val = Object.assign({}, runtime_val);
                }else{
                    runtime_val = IEArrToObject(runtime_val);
                }
                
                delete runtime_val[code_val];
                sel_this.attr("value", JSON.stringify(runtime_val));
                $("#"+this_id_hide).val(JSON.stringify(runtime_val));
            });
            
            sel_commun.change(function(){
                var data_c = JSON.stringify($data_commun);
                var data_commun = JSON.parse(data_c);
                var this_code = $(this).attr("code");
                class_del_button = "del_"+"$this_id";
                sel_this = $("#$this_id");
                this_id_hide = "$this_id"+"_hide";
                
                if($("#"+class_del_button+"_"+this_code).length === 0 && this_code !== '' && this_code !== undefined){
                    sel_this.append("<div style='display:table;' class='"+class_del_button+"_str' id='"+class_del_button+"_"+this_code+"' ><div style='display:table-cell;padding:3px;'>"+data_commun[this_code]+"</div><div class='"+class_del_button+"' code='"+this_code+"' style='display:table-cell;padding:3px;cursor:pointer;'>&#9746;</div></div>");
                    var runtime_val = JSON.parse(sel_this.attr("value"));
                    
                    var isIE = false || !!document.documentMode;
                    var isEdge = !isIE && !!window.StyleMedia;
                    if(isIE === false && isEdge === false){
                        runtime_val = Object.assign({}, runtime_val);
                    }else{
                        runtime_val = IEArrToObject(runtime_val);
                    }
                    
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

    public static function ecocombo($model, $name, $data='', $options='', $set_value=null, $search_case="i", $max_count=100, $max_show=8){

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

        //$data = htmlspecialchars($data);

        $tag_source = '<input type="text" id="'.$this_id.'" name="'.$class_name.'['.$name.']" '.$options.' value="'.$set_value.'" />';

        echo $tag_source;

        $jsTag =<<<JS

        
        var selector_this_id = $("#$this_id");
        
        var p_search_case = "$search_case"; 
        var p_max_count = $max_count;
        var p_max_show = $max_show;
        var p_data = $data;

        //ecocombo(selector_this_id, p_search_case, p_max_count, p_max_show, p_data);

        var anon_ecocombo_$this_id = function(selector, s_case, max_count, max_show, data_in){

            max_count = max_count || 100; 
            max_show = max_show || 8; 
            data_in = data_in || '';
            
            var source;
            var O_K_source;

            if(data_in === '' || data_in === undefined){}else{
                data_in = JSON.stringify(data_in);
                source = JSON.parse(data_in);
                source = arr_sorti_local(source);
                O_K_source = Object.keys(source);
            }

            var this_id = selector.attr("id")+"_btn";
            selector.parent().append('<div id="'+this_id+'" class="ecocombo_btn" style="position:relative;left:'+(selector.width()-16)+'px;top:-24px;width:18px;height:16px;font-size:9px;cursor:pointer;text-align:center;padding-top:5px;">&#9660;</div>');
            var button_selector = $('#'+this_id);

            function arr_sorti_local(arr, sort_stb){
                
                sort_stb = sort_stb || 0;
                
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
                        
                        cbutton.attr('diap_s', diap_s);
                        cbutton.attr('diap_e', diap_e);
                        clist.html('');
                        list_id = clist.attr('id');

                        for(var i = diap_s; i < 1*diap_e; i++){
                            if(O_K_source[i] !== undefined){
                                clist.append('<div class="'+list_id+'_option_row" code="'+cdata[O_K_source[i]][1]+'" onmouseover="this.style.backgroundColor=&quot;#DCDCDC&quot;;" onmouseout="this.style.backgroundColor=&quot;#FFFFFF&quot;;" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><div style="display:table-cell;padding:3px;font-weight:bold;">'+cdata[O_K_source[i]][1]+'</div><div style="display:table-cell;padding:3px;text-overflow:ellipsis;">'+cdata[O_K_source[i]][2]+'</div></div>');
                            }
                        }
                        
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

                    }
                }
            }
            
            function recurs_data_list_i(data_text, cur_value, final_text, step){
                
                step = step || 0;
                
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
                    // больше max_count в селекте нет смысла отображать для поиска
                    if(step < max_count){
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
    
            function recurs_data_list(data_text, cur_value, final_text, step){
                
                step = step || 0;
                
                step = step + 1;
                
                if(cur_value === ','){if(1*data_text.indexOf(cur_value) === (1 + 1*data_text.indexOf('"'+cur_value+'"'))){return false;}}
                else if(cur_value === '"'){return false;}
                else if(cur_value === ''){return false;}
                else if(cur_value === '{'){return false;}
                else if(cur_value === '}'){return false;}
                else if(cur_value === ':'){if(1*data_text.indexOf(cur_value) === (1 + 1*data_text.indexOf('"'+cur_value+'"'))){return false;}}

                if(data_text.indexOf(cur_value) !== -1){
                    // больше max_count в селекте нет смысла отображать для поиска
                    if(step < max_count){
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

            var current_list;
            
            var last_event_mover;
            
            $(document).on("mouseover", 
                "#"+selector.attr("id")+"_list", 
                function(e){
                    if(e.originalEvent !== undefined){
                        if(last_event_mover === 0){
                            $("."+selector.attr("id")+"_list_option_row").css('backgroundColor', '#FFFFFF');
                        }
                        last_event_mover = 1;
                    }else{
                        last_event_mover = 0;
                    }
                }
            );

            selector.keyup(function(e){

                if(e.keyCode === 40 || e.keyCode === 38){
                    return false;
                }

                button_selector.attr('diap_s', '');
                button_selector.attr('diap_e', '');
                
                $(this).attr('code', '');
                $(this).attr('value', '');
                if($('#'+selector.attr('id')+'_list').length > 0){ $('#'+selector.attr('id')+'_list').remove(); }
                if(data_in === undefined){return false;}
                var data_text_clear = '';
                var cur_value = $(this).val();
                
                if(s_case === 'i'){
                    data_text_clear = recurs_data_list_i(data_in, cur_value, undefined);
                }else{
                    data_text_clear = recurs_data_list(data_in, cur_value, undefined);
                }

                if(data_text_clear === false){return false;}
    
                var source = JSON.parse(data_text_clear);
                source = arr_sorti_local(source);
                
                var list_id = selector.attr('id')+'_list';
                var list_html = '<div id="'+list_id+'" style="top:'+ (selector.position().top + selector.height() + 6) +'px;left:'+ selector.position().left +'px;overflow-y:scroll;position:absolute;z-index:1000;background-color:white;padding:3px;cursor:default;width:'+(selector.width() + 5)+'px;max-height:'+(26*max_show)+'px;"></div>';
                selector.parent().append(list_html);
                
                var list_selector_filter = $('#'+list_id);
                list_selector_filter.mouseleave(function(){ $(this).remove(); });
                var fl_hover_list = 0;var fl_hover_btn = 0;var fl_hover_genselector = 0;
                button_selector.unbind('mouseenter').unbind('mouseleave');
                button_selector.hover(
                    function() {
                        fl_hover_btn = 1;
                    }, function() {
                        fl_hover_btn = 0;
                        setTimeout(function(){if(fl_hover_list !== 1 && fl_hover_genselector !== 1){list_selector_filter.remove();}}, 0);
                    }
                );
                list_selector_filter.hover(
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
                        setTimeout(function(){if(fl_hover_list !== 1 && fl_hover_btn !== 1){list_selector_filter.remove();}}, 0);
                    }
                );
    
                for(var i in source){
                    list_selector_filter.append('<div class="'+list_id+'_option_row" code="'+source[O_K_source[i]][1]+'" onmouseover="this.style.backgroundColor=&quot;#DCDCDC&quot;;" onmouseout="this.style.backgroundColor=&quot;#FFFFFF&quot;;" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><div style="display:table-cell;padding:3px;font-weight:bold;">'+source[O_K_source[i]][1]+'</div><div style="display:table-cell;padding:3px;">'+source[O_K_source[i]][2]+'</div></div>');
                }
                
                current_list = $('#'+list_id)[0].outerHTML;

            });
            
            $(document).keydown(function(e){
                var selector_list = $('#'+selector.attr('id')+'_list');
                if(selector_list.length > 0){
                    if(e.keyCode === 40){
                        var onmo;
                        var index_select = -1;
                        var selector_row = $('.'+selector.attr('id')+'_list_option_row');
                        var fl_overlim = 0;
                        selector_row.each(function(i, el){
                            onmo = $(el).attr('onmouseover');
                            if(onmo.search(new RegExp(rgb_to_hex($(el).css('backgroundColor')), "i")) !==-1 ){
                                index_select = 1*i+1;
                                if(selector_row.eq(index_select).length > 0){
                                    selector_list.scrollTop(1*selector_list.scrollTop()+26);
                                    selector_row.eq(i).css('backgroundColor', '#FFFFFF');
                                }else{
                                    if(button_selector.attr('diap_s')!==''){
                                        combo_step_scroll(selector_list, button_selector, source, 1);
                                        index_select = 1*i;
                                        fl_overlim = 1;
                                        return false;
                                    }
                                }
                            }
                        });
                        if(index_select === -1){index_select = 0;}
                        if(fl_overlim === 0){
                            selector_row.eq(index_select).mouseover();
                        }else{
                            $('.'+selector.attr('id')+'_list_option_row').eq(index_select).mouseover();
                        }
                    }else if(e.keyCode === 38){
                        var onmo;
                        var index_select = -1;
                        var selector_row = $('.'+selector.attr('id')+'_list_option_row');
                        var fl_overlim = 0;
                        selector_row.each(function(i, el){
                            onmo = $(el).attr('onmouseover');
                            if(onmo.search(new RegExp(rgb_to_hex($(el).css('backgroundColor')), "i")) !==-1 ){
                                index_select = 1*i-1;
                                if(index_select >= 0){
                                    selector_list.scrollTop(1*selector_list.scrollTop()-26);
                                    selector_row.eq(i).css('backgroundColor', '#FFFFFF');
                                }else{
                                    if(button_selector.attr('diap_s')!==''){
                                        combo_step_scroll(selector_list, button_selector, source, 2);
                                        index_select = 0;
                                        fl_overlim = 1;
                                        return false;
                                    }
                                }
                            }
                        });
                        if(index_select === -1){index_select = 0;}
                        if(fl_overlim === 0){
                            selector_row.eq(index_select).mouseover();
                        }else{
                            $('.'+selector.attr('id')+'_list_option_row').eq(index_select).mouseover();
                        }
                    }else if(e.keyCode === 13){
                        var onmo;
                        var selector_row = $('.'+selector.attr('id')+'_list_option_row');
                        selector_row.each(function(i, el){
                            onmo = $(el).attr('onmouseover');
                            if(onmo.search(new RegExp(rgb_to_hex($(el).css('backgroundColor')), "i")) !==-1 ){
                                selector_row.eq(i).click();
                            }
                        });
                    }
                }
            });
            
            function rgb_to_hex(color){
                var rgb = color.replace(/\s/g,'').match(/^rgba?\((\d+),(\d+),(\d+)/i);
                return (rgb && rgb.length === 4) ? "#" + ("0" + parseInt(rgb[1],10).toString(16)).slice(-2) + ("0" + parseInt(rgb[2],10).toString(16)).slice(-2) + ("0" + parseInt(rgb[3],10).toString(16)).slice(-2) : color;
            }
            
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
                
                current_list = '';
                
            });
            
            var current_value;
            
            button_selector.click(function(){
                
                if($('#'+selector.attr('id')+'_list').length > 0){
                    $('#'+selector.attr('id')+'_list').remove();
                }
                
                current_value = selector.val();
                var dpos = data_in.indexOf(current_value);
                
                if(dpos !== -1 && current_value.trim() !== ''){
                    selector.parent().append(current_list);
                    var list_id = selector.attr('id')+'_list';
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

                }else{
                
                    if( $('#'+selector.attr('id')+'_list').length > 0){ $('#'+selector.attr('id')+'_list').remove();}
                    var attr_start = 0;
                    var attr_s = $(this).attr('diap_s');
                    var attr_end = 1*max_show;
                    var attr_e = $(this).attr('diap_e');
                    $(this).attr('diap_s', 0);
                    $(this).attr('diap_e', max_show);
                    if(data_in === '' || data_in === undefined){return false;}
                    
                    var list_id = selector.attr('id')+'_list';
                    var list_html = '<div id="'+list_id+'" style="top:'+ (selector.position().top + selector.height() + 6) +'px;left:'+ selector.position().left +'px;overflow-y:scroll;position:absolute;z-index:1000;background-color:white;padding:3px;cursor:default;width:'+(selector.width() + 5)+'px;"></div>';
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
                            combo_step_scroll($(this), button_selector, source, 1);
                        } else {
                            //scroll up
                            combo_step_scroll($(this), button_selector, source, 2);
                        }
                    });
                    
                    for(var i = attr_start; i < 1*attr_end; i++){
        
                        if(O_K_source[i] !== undefined){
                            list_selector.append('<div class="'+list_id+'_option_row" code="'+source[O_K_source[i]][1]+'" onmouseover="this.style.backgroundColor=&quot;#DCDCDC&quot;;" onmouseout="this.style.backgroundColor=&quot;#FFFFFF&quot;;" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-height:21px;"><div style="display:table-cell;padding:3px;font-weight:bold;">'+source[O_K_source[i]][1]+'</div><div style="display:table-cell;padding:3px;">'+source[O_K_source[i]][2]+'</div></div>');
                        }else{
                            $(this).attr('diap_e', 1*i);
                            break;
                        }
                    }
                }
            });
        };

        anon_ecocombo_$this_id(selector_this_id, p_search_case, p_max_count, p_max_show, p_data);
        
JS;

        Yii::$app->view->registerJs($jsTag);

    }

    public static function monetary_field($model, $name, $this_total=4, $this_fraction=2, $options='', $set_value=null)
    {

        $plshold = '';
        $ost = '';
        $this_id = self::tag_symb($name);
        for ($i = 0; $i < $this_total; $i++) {
            if (1 * $i === 1 * $this_fraction) {
                $plshold = '.' . $plshold;
                $ost = $plshold;
                $plshold = '0' . $plshold;
            } else {
                $plshold = '0' . $plshold;
            }
        }

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

        if(GenF::index_of($options, 'style')===-1){
            $options = $options.' style="width:250px;text-align:right;"';
        }

        $tag_source = '<input type="text" id="'.$this_id.'" name="'.$class_name.'['.$name.']" '.$options.' value="'.$set_value.'" placeholder="'.$plshold.'" />';

        echo $tag_source;

        $jsTag =<<<JS
        
            monetary_field("$this_id", $this_total, $this_fraction, "$ost");
        
            new function($) {
              $.fn.setCursorPosition = function(pos){
                if (this[0].setSelectionRange) {
                  this[0].setSelectionRange(pos, pos);
                } else if (this.createTextRange) {
                  var range = this.createTextRange();
                  range.collapse(true);
                  if(pos < 0) {
                    pos = $(this).val().length + pos;
                  }
                  range.moveEnd("character", pos);
                  range.moveStart("character", pos);
                  range.select();
                }
              }
            }(jQuery);
            
            function is_int_chr(chr){
                if(chr === 48 || chr === 49 || chr === 50 || chr === 51 || chr === 52 || chr === 53 || chr === 54 || chr === 55 || chr === 56 || chr === 57){return true;}else{return false;}
            }
            
            function is_double_chr(chr){
                if(chr === 46 || chr === 48 || chr === 49 || chr === 50 || chr === 51 || chr === 52 || chr === 53 || chr === 54 || chr === 55 || chr === 56 || chr === 57){return true;}else{return false;}
            }
            
            function monetary_field(this_id, this_total, this_fraction, ost){

            var total = 1 * this_total;
            var fraction = 1 * this_fraction;

            var selector_this_id = $('#'+this_id);
            selector_this_id.keydown(function(e){
                if(e.keyCode == 8 || e.keyCode == 46){
                    var cur_val = $(this).val();
                    var cur_pos = this.selectionStart;
                    var first_path = cur_val.substr(0, cur_pos);
                    var second_path = cur_val.substr(cur_pos);
                    var ceil_path = cur_val.substr(0, cur_val.indexOf("."));
                    var first_path_l_s = first_path.substr(first_path.length-1);
                    if(first_path_l_s == "." && this.selectionStart === this.selectionEnd){return false;}
                    if(cur_pos > fraction){
                        if(this.selectionStart === this.selectionEnd){
                            first_path = first_path.substr(0, first_path.length-1);
                            if(first_path + "0" + second_path === "0"){return false;}
                            $(this).val(first_path + "0" + second_path);
                            $(this).setCursorPosition(cur_pos-1);
                            return false;
                        }
                    }else{
                        if((this.selectionEnd - this.selectionStart) === cur_val.length){
                            $(this).val(""); 
                            return false;
                        }
                        if(this.selectionStart == this.selectionEnd){
                            $(this).val((1*(first_path.substr(0, first_path.length-1) + second_path)).toFixed(fraction)); $(this).setCursorPosition(cur_pos-1); return false;
                        }else{
                            second_path = second_path.substr(this.selectionEnd - this.selectionStart);
                            if(second_path.indexOf(".") === -1){second_path = "."+second_path;}
                            $(this).val((1*(first_path + second_path)).toFixed(fraction)); $(this).setCursorPosition(cur_pos); return false;
                        }
                    }
                    $(this).val((1* (first_path) ).toFixed(fraction)); $(this).setCursorPosition(cur_pos); return false;
                };
            });

            selector_this_id.keypress(function(e){
                if(is_double_chr(e.charCode)){
                    if($(this).val()=="" && is_int_chr(e.charCode)){
                        $(this).val(String.fromCharCode(e.charCode) + ost); 
                        $(this).setCursorPosition(1); 
                        return false;
                    }
                    var cur_str_val = $(this).val();
                    
                    if(e.charCode === 46 && cur_str_val.indexOf(".") !== -1){
                        var substr_cur_str_val = cur_str_val.substr(0, cur_str_val.indexOf("."));
                        var count_cur_str_val = substr_cur_str_val.length + 1;
                        $(this).setCursorPosition(count_cur_str_val); 
                        return false;
                    }

                    if(cur_str_val.length > total){
                        var cur_pos = this.selectionStart;
                        var cur_val = $(this).val();
                        var first_path = cur_val.substr(0, cur_pos);
                        var second_path = cur_val.substr(cur_pos);
                        
                        var fraction_position = cur_val.indexOf('.');

                        if(cur_pos > fraction_position){
                            if(second_path == ""){return false;}
                            second_path = second_path.substr(this.selectionEnd - this.selectionStart);
                            var ifcurval = first_path + String.fromCharCode(e.charCode) + second_path;
                            $(this).val((1*ifcurval).toFixed(fraction));
                            $(this).setCursorPosition(cur_pos+1);
                        }else{
                            if(cur_pos === fraction_position){
                                $(this).setCursorPosition(cur_pos+1); 
                                return false;
                            }
                            step_path = this.selectionEnd - this.selectionStart;
                            if(step_path == 0){step_path = 1;}
                            second_path = second_path.substr(step_path);
                            if(second_path.indexOf(".") === -1){second_path = "."+second_path;}
                            var ifcurval = first_path + String.fromCharCode(e.charCode) + second_path;
                            $(this).val((1*ifcurval).toFixed(fraction));
                            $(this).setCursorPosition(cur_pos+1);
                        }
                        return false;
                    }else{
                        var cur_pos = this.selectionStart;
                        var cur_val = $(this).val();
                        var first_path = cur_val.substr(0, cur_pos);
                        var second_path = cur_val.substr(cur_pos, (cur_val.length - 1*cur_pos));
                        var all_path = first_path + String.fromCharCode(e.charCode) + second_path;
                        var fraction_path = all_path.substr(all_path.indexOf(".") + 1);
                        if(fraction_path.length > fraction){all_path = all_path.substr(0, all_path.length - 1);}
                        $(this).val((1 * (all_path)).toFixed(fraction)); $(this).setCursorPosition(cur_pos+1); return false;
                    }
                }else{return false;}
            });
        }
JS;

        Yii::$app->view->registerJs($jsTag);

    }

    }
?>