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
            $prom_arr_class = explode("\\", get_class($model));
            $class_name = end($prom_arr_class);
        }

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
            
            if(typeof arr_value[Object.keys(arr_value)[0]] !== 'object'){
                for(var key in arr_value){
                    source_this = source_this + "<div style='display:table;' class='"+class_del_button+"_str' id='"+class_del_button+"_"+key+"' ><div class='"+class_del_button+"' code='"+key+"' id='" + "$this_id" + "_" + arr_value[key] + "' style='display:table-cell;padding:3px;cursor:pointer;'>&#9746;</div><div style='display:table-cell;padding:3px;text-align:left;' clear_name='"+arr_value[key]+"' code='"+key+"'>"+arr_value[key]+"</div></div>";
                }
            }else{
                for(var key in arr_value){
                    source_this = source_this + "<div style='display:table;' class='"+class_del_button+"_str' id='"+class_del_button+"_"+key+"' ><div class='"+class_del_button+"' code='"+key+"' id='" + "$this_id" + "_" + arr_value[key][0] + "' style='display:table-cell;padding:3px;cursor:pointer;'>&#9746;</div><div style='display:table-cell;padding:3px;text-align:left;' clear_name='"+arr_value[key][0]+"' code='"+key+"'>"+arr_value[key][0]+" - <span style='font-style:italic;font-size:12px;'>"+arr_value[key][1]+"</span></div></div>";
                }
            }

            sel_this.append(source_this);

            var isIE = false || !!document.documentMode;
            var isEdge = !isIE && !!window.StyleMedia;
            
            if(isIE !== false || isEdge !== false){
                var IEArrToObject__$this_id = function(arr) {
                    var rv = {};
                    for (var i = 0; i < arr.length; ++i){
                        rv[i] = arr[i];
                    }
                    return rv;
                };
            }
            
            $(document).on("click", "."+class_del_button, function(e){
                
                var this_id = $('#'+$(this).attr('id'));
                var this_class = $("."+class_del_button);
                
                $(document).trigger(class_del_button+"_monitor_1" [$(this), e]);

                class_del_button = "del_"+"$this_id";
                sel_this = $("#$this_id");
                this_id_hide = "$this_id"+"_hide";
                
                var code_val = $(this).attr("code");
                //$("#"+class_del_button+"_"+code_val).remove();
                var runtime_val = JSON.parse(sel_this.attr("value"));

                if(isIE === false && isEdge === false){
                    runtime_val = Object.assign({}, runtime_val);
                }else{
                    runtime_val = IEArrToObject__$this_id(runtime_val);
                }
                
                delete runtime_val[code_val];
                sel_this.attr("value", JSON.stringify(runtime_val));
                $("#"+this_id_hide).val(JSON.stringify(runtime_val));

                $(document).trigger(class_del_button+"_monitor_2", [$(this), e, runtime_val]);

                $("#"+class_del_button+"_"+code_val).remove();
                
            });
            
            sel_commun.change(function(e){
                var data_c = JSON.stringify($data_commun);
                var data_commun = JSON.parse(data_c);
                
                if(typeof data_commun[Object.keys(data_commun)[0]] !== 'object'){
                
                    var this_code = $(this).attr("code");
                    class_del_button = "del_"+"$this_id";
                    sel_this = $("#$this_id");
                    this_id_hide = "$this_id"+"_hide";
    
                    if($("#"+class_del_button+"_"+this_code).length === 0 && this_code !== '' && this_code !== undefined){
                        
                        $(this).trigger("monitor_1", [$(this), e]);
                        
                        sel_this.append("<div style='display:table;' class='"+class_del_button+"_str' id='"+class_del_button+"_"+this_code+"' ><div class='"+class_del_button+"' code='"+this_code+"' id='" + "$this_id" + "_" + data_commun[this_code] + "' style='display:table-cell;padding:3px;cursor:pointer;'>&#9746;</div><div style='display:table-cell;padding:3px;text-align:left;' clear_name='"+data_commun[this_code]+"' code='"+this_code+"'>"+data_commun[this_code]+"</div></div>");
                        var runtime_val = JSON.parse(sel_this.attr("value"));
                        
                        if(isIE === false && isEdge === false){
                            runtime_val = Object.assign({}, runtime_val);
                        }else{
                            runtime_val = IEArrToObject__$this_id(runtime_val);
                        }
                        
                        runtime_val[''+this_code] = data_commun[this_code];
                        sel_this.attr("value", JSON.stringify(runtime_val));
                        $("#"+this_id_hide).val(JSON.stringify(runtime_val));
                        
                        $(this).trigger("monitor_2", [$(this), e, runtime_val]);
                        
                    }else{
                        return false;
                    }
                
                }else{
                    // width comment
                    var this_code = $(this).attr("code");
                    class_del_button = "del_"+"$this_id";
                    sel_this = $("#$this_id");
                    this_id_hide = "$this_id"+"_hide";
    
                    if($("#"+class_del_button+"_"+this_code).length === 0 && this_code !== '' && this_code !== undefined){
                        
                        $(this).trigger("monitor_1", [$(this), e]);
                        
                        sel_this.append("<div style='display:table;' class='"+class_del_button+"_str' id='"+class_del_button+"_"+this_code+"' ><div class='"+class_del_button+"' id='" + "$this_id" + "_" + data_commun[this_code][0] + "' code='"+this_code+"' style='display:table-cell;padding:3px;cursor:pointer;'>&#9746;</div><div style='display:table-cell;padding:3px;text-align:left;' clear_name='"+data_commun[this_code][0]+"' code='"+this_code+"'>"+data_commun[this_code][0]+" - <span style='font-style:italic;font-size:12px;'>"+data_commun[this_code][1]+"</span></div></div>");
                        var runtime_val = JSON.parse(sel_this.attr("value"));

                        if(isIE === false && isEdge === false){
                            runtime_val = Object.assign({}, runtime_val);
                        }else{
                            runtime_val = IEArrToObject__$this_id(runtime_val);
                        }
                        runtime_val[''+this_code] = data_commun[this_code];
                        sel_this.attr("value", JSON.stringify(runtime_val));
                        $("#"+this_id_hide).val(JSON.stringify(runtime_val));
                        
                        $(this).trigger("monitor_2", [$(this), e, runtime_val]);
                        
                    }else{
                        return false;
                    }
                    
                }
                
            });
            
JS;
        Yii::$app->view->registerJs($jsTag);
    }

    public static function input($model, $name, $options='', $set_value=null){
        $this_id = self::tag_symb($name);

        $prom_arr_class = explode("\\", get_class($model));
        $class_name = end($prom_arr_class);

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

    public static function ecocombo($model, $name, $data='', $options='', $set_value=null, $search_case="i", $max_count=100, $max_show=8, $key_hide=0, $width_list=0){

        if(is_array($data) || is_object($data)){
            if(is_array($data)){$data = GenF::array_to_object($data);}
            foreach($data as $k=>$v){
                if(is_string($v)){
                    $data->{$k} = htmlspecialchars($v);
                }
            }
            $data = json_encode($data,1);
        }

        if($data == ''){
            $data = Json::encode((object)[]);
        }

        $this_id = self::tag_symb($name);
        if(is_string($model)){
            $class_name = $model;
        }else{
            $prom_arr_class = explode("\\", get_class($model));
            $class_name = end($prom_arr_class);
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

        $tag_source = '<div style="height:27px;"><div id="'.$this_id.'_id_outer" style="display:inline-block;height:27px;float:left;"><input type="text" id="'.$this_id.'" name="'.$class_name.'['.$name.']" '.$options.' value="'.$set_value.'" /></div></div>';

        echo $tag_source;

        $jsTag =<<<JS

        var selector_this_id = $("#$this_id");
        
        var p_search_case = "$search_case"; 
        var p_max_count = $max_count;
        var p_max_show = $max_show;
        var p_data = $data;
        var key_hide = $key_hide;
        var width_list = $width_list;
        
        var anon_ecocombo_$this_id = function(selector, s_case, max_count, max_show, data_in, key_hide, width_list){

            max_count = max_count || 100; 
            max_show = max_show || 8; 
            data_in = data_in || '';
            key_hide = key_hide || 0;
            
            var open_tag = '{';
            var close_tag = '}';
            
            var source;
            var O_K_source;
            
            var fl_class = 0;
            if(selector.attr('class')!==undefined && selector.attr('class')!==''){
                fl_class = 1;
            }

            if(fl_class === 1){
                var classList = selector.attr('class').split(/\s+/);
                var str_selector_class = '';
                for(var keyclass in classList){
                    if(1*keyclass === 0){
                        str_selector_class = '.'+classList[keyclass];
                    }else{
                        str_selector_class = str_selector_class+', .'+classList[keyclass];
                    }
                }
                
                selector.change(function(e){
                    setTimeout(function(){ $(str_selector_class).trigger("ecocombo_on_change", [selector, e]); }, 0);
                });
            }
            
            if(data_in === '' || data_in === undefined){}else{
                data_in = JSON.stringify(data_in);
                source = JSON.parse(data_in);
                source = arr_sorti_local(source);
                O_K_source = Object.keys(source);
            }

            var this_id = selector.attr("id")+"_btn";
            selector.parent().append('<div id="'+this_id+'" class="'+this_id+'_class_ecocombo_btn" style="position:relative;left:'+(selector.width()+3)+'px;top:-24px;width:18px;height:25px;font-size:9px;cursor:pointer;text-align:center;padding-top:5px;">&#9660;</div>');
            var button_selector = $('#'+this_id);

            function arr_sorti_local(arr, sort_stb){
                
                sort_stb = sort_stb || 0;

                var flag = 0;
                var sortable = [];
                for (var vehicle in arr) {
                    if(typeof arr[vehicle] === 'object'){
                        if(flag === 0){flag = 1;}
                        var rt_arr = [1*vehicle, vehicle];
                        for (var vehicle2 in arr[vehicle]) {
                            rt_arr.push(arr[vehicle][vehicle2]);
                        }
                        sortable.push(rt_arr);
                    }else{
                        sortable.push([1*vehicle, vehicle, arr[vehicle]]);
                    }
                }
                
                if(flag === 1){
                    open_tag = '{{';
                    close_tag = '}}';
                }
                
                sortable.sort(function(a, b) {return a[sort_stb] - b[sort_stb]});
                return sortable;
            }
            
            function draw_list(clist, diap_s, diap_e, O_K_source, source, list_id){
                if(diap_s === 'none' && diap_e === 'none'){
                    for(var inkey in source){
                        if(O_K_source[inkey] !== undefined){
                            var comment_dop = '';
                            if(source[O_K_source[inkey]][3]){comment_dop='<div style="display:table-cell;font-style:italic;color:grey;font-size:10px;">'+source[O_K_source[inkey]][3]+'</div>';}
                            if(key_hide === 0){
                                clist.append('<div class="'+list_id+'_option_row" code="'+source[O_K_source[inkey]][1]+'" clear_name="'+source[O_K_source[inkey]][2]+'" onmouseover="this.style.backgroundColor=&quot;#DCDCDC&quot;;" onmouseout="this.style.backgroundColor=&quot;#FFFFFF&quot;;" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><div class="'+list_id+'_opt_key" style="display:table-cell;padding:3px;font-weight:bold;">'+source[O_K_source[inkey]][1]+'</div><div class="'+list_id+'_opt_val"  style="display:table-cell;padding:3px;text-overflow:ellipsis;">'+source[O_K_source[inkey]][2]+'</div>'+comment_dop+'</div>');
                            }else{
                                clist.append('<div class="'+list_id+'_option_row" code="'+source[O_K_source[inkey]][1]+'" clear_name="'+source[O_K_source[inkey]][2]+'" onmouseover="this.style.backgroundColor=&quot;#DCDCDC&quot;;" onmouseout="this.style.backgroundColor=&quot;#FFFFFF&quot;;" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><div class="'+list_id+'_opt_val" style="display:table-cell;padding:3px;text-overflow:ellipsis;">'+source[O_K_source[inkey]][2]+'</div>'+comment_dop+'</div>');
                            }
                        }
                    }
                }else{
                    for(var i = diap_s; i < 1*diap_e; i++){
                        if(O_K_source[i] !== undefined){
                            var comment_dop = '';
                            if(source[O_K_source[i]][3]){comment_dop='<div style="display:table-cell;font-style:italic;color:grey;font-size:10px;">'+source[O_K_source[i]][3]+'</div>';}
                            if(key_hide === 0){
                                clist.append('<div class="'+list_id+'_option_row" code="'+source[O_K_source[i]][1]+'" clear_name="'+source[O_K_source[i]][2]+'" onmouseover="this.style.backgroundColor=&quot;#DCDCDC&quot;;" onmouseout="this.style.backgroundColor=&quot;#FFFFFF&quot;;" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><div class="'+list_id+'_opt_key" style="display:table-cell;padding:3px;font-weight:bold;">'+source[O_K_source[i]][1]+'</div><div class="'+list_id+'_opt_val"  style="display:table-cell;padding:3px;text-overflow:ellipsis;">'+source[O_K_source[i]][2]+'</div>'+comment_dop+'</div>');
                            }else{
                                clist.append('<div class="'+list_id+'_option_row" code="'+source[O_K_source[i]][1]+'" clear_name="'+source[O_K_source[i]][2]+'" onmouseover="this.style.backgroundColor=&quot;#DCDCDC&quot;;" onmouseout="this.style.backgroundColor=&quot;#FFFFFF&quot;;" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><div class="'+list_id+'_opt_val" style="display:table-cell;padding:3px;text-overflow:ellipsis;">'+source[O_K_source[i]][2]+'</div>'+comment_dop+'</div>');
                            }
                        }
                    }
                }
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
                        var list_id = clist.attr('id');

                        draw_list(clist, diap_s, diap_e, O_K_source, cdata, list_id);
                        
                    }else if(fl === 2){
                        diap_s = 1*cbutton.attr('diap_s') - 1;
                        diap_e = 1*cbutton.attr('diap_e') - 1;
                        if(diap_s < 0){
                            return false;
                        }
                        cbutton.attr('diap_s', diap_s);
                        cbutton.attr('diap_e', diap_e);
                        clist.html('');
                        var list_id = clist.attr('id');

                        draw_list(clist, diap_s, diap_e, O_K_source, cdata, list_id);
                    }
                }
            }
            
            function recurs_data_list(data_text, cur_value, final_text, step, regexp_config){
                
                step = step || 0;
                regexp_config = regexp_config || "";
                
                step = step + 1;

                if(cur_value === ','){if(1*data_text.search(new RegExp(cur_value, regexp_config)) === (1 + 1*data_text.search(new RegExp('"'+cur_value+'"', regexp_config)))){return false;}}
                else if(cur_value === '"'){return false;}
                else if(cur_value === ''){return false;}
                else if(cur_value === '{'){return false;}
                else if(cur_value === '}'){return false;}
                else if(cur_value === '['){return false;}
                else if(cur_value === ']'){return false;}
                else if(cur_value === ':'){if(1*data_text.search(new RegExp(cur_value, regexp_config)) === (1 + 1*data_text.search(new RegExp('"'+cur_value+'"', regexp_config)))){return false;}}

                if(open_tag === '{' && close_tag === '}'){
                    if(data_text.search(new RegExp(cur_value, regexp_config)) !== -1){
                        // больше max_count в селекте нет смысла отображать для поиска
                        if(step < max_count){
                            var prom_data_begin = data_text.substr(0, data_text.search(new RegExp(cur_value, regexp_config)));
                            if(prom_data_begin.lastIndexOf('","') !== -1){
                                var index_begin = prom_data_begin.lastIndexOf('","') + ('","').length;}
                            else{
                                if(prom_data_begin.indexOf(open_tag) === 0){
                                    var index_begin = 0;
                                    if(final_text === undefined){final_text = '';}
                                }
                            }
                            if(final_text === undefined){final_text = open_tag+'"';}
                            var prom_data_end = data_text.substr(data_text.search(new RegExp(cur_value, regexp_config)));
                            var prom_data_end_delim = prom_data_end.indexOf('","');
                            if(prom_data_end_delim === -1){prom_data_end_delim = prom_data_end.indexOf(close_tag);}
                            var index_end = 1*(data_text.search(new RegExp(cur_value, regexp_config))) + 1*prom_data_end_delim;
                            final_text = final_text + data_text.substr(1*index_begin , (1*(data_text.search(new RegExp(cur_value, regexp_config))) - 1*index_begin) + 1*prom_data_end_delim)+'","';
                            data_text = data_text.substr(1*index_begin + (1*(data_text.search(new RegExp(cur_value, regexp_config))) - 1*index_begin) + 1*prom_data_end_delim);
                            return recurs_data_list(data_text, cur_value, final_text, step, regexp_config);
                        }else{
                            if(final_text !== undefined){
                                if(final_text.indexOf('","') !== -1){
                                    if(final_text.indexOf('"","')!==-1){
                                        if(final_text.length === (final_text.indexOf('"","')+('"","').length)){
                                            final_text = final_text.substr(0, final_text.length - ('","').length)+''+close_tag;
                                        }else{
                                            final_text = final_text.substr(0, final_text.length - ('","').length)+'"'+close_tag;
                                        }
                                    }else{final_text = final_text.substr(0, final_text.length - ('","').length)+'"'+close_tag;}
                                    return final_text;
                                }else{return false;}
                            }else{return false;}
                        }
                    }else{
    
                        if(final_text !== undefined){
                            if(final_text.indexOf('","') !== -1){
                                if(final_text.indexOf('"","')!==-1){
                                    if(final_text.length === (final_text.indexOf('"","')+('"","').length)){
                                        final_text = final_text.substr(0, final_text.length - ('","').length)+''+close_tag;
                                    }else{
                                        final_text = final_text.substr(0, final_text.length - ('","').length)+'"'+close_tag;
                                    }
                                }else{final_text = final_text.substr(0, final_text.length - ('","').length)+'"'+close_tag;}
                                return final_text;
                            }else{return false;}
                        }else{return false;}
                    }
                }else{

                    if(data_text.search(new RegExp(cur_value, regexp_config)) !== -1){
                        // больше max_count в селекте нет смысла отображать для поиска
                        if(step < max_count){
                            
                            var prom_data_begin = data_text.substr(0, data_text.search(new RegExp(cur_value, regexp_config)));

                            if(prom_data_begin.lastIndexOf('},"') !== -1){
                                var index_begin = prom_data_begin.lastIndexOf('},"') + ('},"').length;}
                            else{
                                //source               = begin: source[Object.keys(source)[0]][Object.keys(source[Object.keys(source)[0]])[0]] - as id in db
                                //as data_text from db = end: 0 (always Zero as in db) ! normalized view matches input data structure ['id1'=>['val1', 'comm1'], 'id2'=>['val2', 'comm2']] for comment
                                if(prom_data_begin.indexOf('{"' + source[Object.keys(source)[0]][Object.keys(source[Object.keys(source)[0]])[0]] + '":{"0":"') === 0){
                                    var index_begin = 0;
                                    if(final_text === undefined){final_text = '';}
                                }
                            }

                            var prom_data_end = data_text.substr(data_text.search(new RegExp(cur_value, regexp_config)));
                            var prom_data_end_delim = prom_data_end.indexOf('},"');
                            if(prom_data_end_delim === -1){prom_data_end_delim = prom_data_end.indexOf(close_tag);}
                            var index_end = 1*(data_text.search(new RegExp(cur_value, regexp_config))) + 1*prom_data_end_delim;
                            
                            var plus_fin = data_text.substr(1*index_begin , (1*(data_text.search(new RegExp(cur_value, regexp_config))) - 1*index_begin) + 1*prom_data_end_delim)+'},"'
                            
                            if(final_text === undefined){
                                var begin_f_t = '{"'+plus_fin;
                                begin_f_t = begin_f_t.substr(0, begin_f_t.indexOf('":"') + ('":"').length);
                                final_text = begin_f_t + plus_fin.substr(plus_fin.indexOf('":"') + ('":"').length);
                            }else{
                                final_text = final_text + plus_fin;
                            }
                            data_text = data_text.substr(1*index_begin + (1*(data_text.search(new RegExp(cur_value, regexp_config))) - 1*index_begin) + 1*prom_data_end_delim);
                            return recurs_data_list(data_text, cur_value, final_text, step, regexp_config);
                        }else{
                            if(final_text !== undefined){
                                if(final_text.indexOf('},"') !== -1){
                                    final_text = final_text.substr(0, final_text.length - ('},"').length)+''+close_tag;
                                    return final_text;
                                }else{return false;}
                            }else{return false;}
                        }
                    }else{
                        if(final_text !== undefined){
                            if(final_text.indexOf('},"') !== -1){
                                final_text = final_text.substr(0, final_text.length - ('},"').length)+''+close_tag;
                                return final_text;
                            }else{return false;}
                        }else{return false;}
                    }
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
                if($('#'+selector.attr('id')+'_list').length > 0){ 
                    $('#'+selector.attr('id')+'_list').remove();
                    $('#'+selector.attr('id')+'_list_outer').remove();
                }
                if(data_in === undefined){return false;}
                var data_text_clear = '';
                var cur_value = $(this).val();
                
                data_text_clear = recurs_data_list(data_in, cur_value, undefined, 0, s_case);

                if(data_text_clear === false){return false;}
                
                var source = JSON.parse(data_text_clear);
                
                source = arr_sorti_local(source);
                
                var list_id = selector.attr('id')+'_list';
                var list_html = '<div id="'+list_id+'_outer" style="width:0px;height:0px;"><div id="'+list_id+'" style="top:'+ (-1*selector.parent().height()) +'px;left:0px;position:relative;overflow-y:auto;z-index:1000;background-color:white;padding:3px;cursor:default;max-height:250px;width:'+(selector.width() + 25 + 1*width_list)+'px;font-size:'+selector.css('font-size')+';border:1px solid grey;"></div></div></div>';
                selector.parent().append(list_html);
                
                var list_selector_filter = $('#'+list_id);
                list_selector_filter.mouseleave(function(){
                    $(this).parent().remove();
                    $(this).remove(); 
                });
                var fl_hover_list = 0;var fl_hover_btn = 0;var fl_hover_genselector = 0;
                button_selector.unbind('mouseenter').unbind('mouseleave');
                button_selector.hover(
                    function() {
                        fl_hover_btn = 1;
                    }, function() {
                        fl_hover_btn = 0;
                        setTimeout(function(){if(fl_hover_list !== 1 && fl_hover_genselector !== 1){
                            list_selector_filter.parent().remove();
                            list_selector_filter.remove();
                        }}, 0);
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
                        setTimeout(function(){if(fl_hover_list !== 1 && fl_hover_btn !== 1){
                            list_selector_filter.parent().remove();
                            list_selector_filter.remove();
                        }}, 0);
                    }
                );

                draw_list(list_selector_filter, 'none', 'none', O_K_source, source, list_id);
                
                current_list = $('#'+list_id).parent()[0].outerHTML;

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
                selector.attr('clear_name', $(this).attr('clear_name'));
                
                if($(this).children('div[class$="_opt_key"]').length > 0){
                    selector.val($(this).children('div[class$="_opt_key"]').html()+' - '+$(this).children('div[class$="_opt_val"]').html());
                }else{
                    selector.val($(this).children('div[class$="_opt_val"]').html());
                }
                
                selector.attr('value', $(this).attr('code'));
                
                selector.change();
                
                if($('#'+selector.attr('id')+'_list').length > 0){
                    $('#'+selector.attr('id')+'_list').remove();
                    $('#'+selector.attr('id')+'_list_outer').remove();
                }
                
                current_list = '';
                
            });
            
            var current_value;
            
            button_selector.click(function(){
                
                if($('#'+selector.attr('id')+'_list').length > 0){
                    $('#'+selector.attr('id')+'_list').remove();
                    $('#'+selector.attr('id')+'_list_outer').remove();
                }
                
                current_value = selector.val();
                var dpos = data_in.indexOf(current_value);

                if(dpos !== -1 && current_value.trim() !== '' && current_list !== '' && current_list !== undefined){

                    selector.parent().append(current_list);
                    var list_id = selector.attr('id')+'_list';
                    var list_selector = $('#'+list_id);
                    list_selector.mouseleave(function(){
                        $(this).parent().remove();
                        $(this).remove();
                    });
                    
                    var fl_hover_list = 0;
                    var fl_hover_btn = 0;
                    var fl_hover_genselector = 0;
        
                    $(this).unbind('mouseenter').unbind('mouseleave');
                    $(this).hover(
                        function() {
                            fl_hover_btn = 1;
                        }, function() {
                            fl_hover_btn = 0;
                            setTimeout(function(){if(fl_hover_list !== 1 && fl_hover_genselector !== 1){
                                list_selector.parent().remove();
                                list_selector.remove();
                            }}, 0);
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
                            setTimeout(function(){if(fl_hover_list !== 1 && fl_hover_btn !== 1){
                                list_selector.parent().remove();
                                list_selector.remove();
                            }}, 0);
                        }
                    );

                }else{
                
                    if( $('#'+selector.attr('id')+'_list').length > 0){ 
                        $('#'+selector.attr('id')+'_list').remove();
                        $('#'+selector.attr('id')+'_list_outer').remove();
                    }
                    var attr_start = 0;
                    var attr_s = $(this).attr('diap_s');
                    var attr_end = 1*max_show;
                    var attr_e = $(this).attr('diap_e');
                    $(this).attr('diap_s', 0);
                    $(this).attr('diap_e', max_show);
                    if(data_in === '' || data_in === undefined){return false;}

                    var list_id = selector.attr('id')+'_list';
                    var list_html = '<div id="'+list_id+'_outer" style="width:0px;height:0px;"><div id="'+list_id+'" style="top:'+ (-1*selector.parent().height()) +'px;left:0px;position:relative;overflow-y:auto;z-index:1000;background-color:white;padding:3px;cursor:default;max-height:250px;width:'+(selector.width() + 25 + 1*width_list)+'px;font-size:'+selector.css('font-size')+';border:1px solid grey;"></div></div>';
                    selector.parent().append(list_html);
                    var list_selector = $('#'+list_id);
                    list_selector.mouseleave(function(){
                        $(this).parent().remove();
                        $(this).remove();
                    });
                    var fl_hover_list = 0;
                    var fl_hover_btn = 0;
                    var fl_hover_genselector = 0;
        
                    $(this).unbind('mouseenter').unbind('mouseleave');
                    $(this).hover(
                        function() {
                            fl_hover_btn = 1;
                        }, function() {
                            fl_hover_btn = 0;
                            setTimeout(function(){if(fl_hover_list !== 1 && fl_hover_genselector !== 1){
                                list_selector.parent().remove();
                                list_selector.remove();
                            }}, 0);
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
                            setTimeout(function(){if(fl_hover_list !== 1 && fl_hover_btn !== 1){
                                list_selector.parent().remove();
                                list_selector.remove();
                            }}, 0);
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

                    draw_list(list_selector, attr_start, attr_end, O_K_source, source, list_id);
                    
                }
            });
        };

        anon_ecocombo_$this_id(selector_this_id, p_search_case, p_max_count, p_max_show, p_data, key_hide, width_list);
        
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
            $prom_arr_class = explode("\\", get_class($model));
            $class_name = end($prom_arr_class);
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