<?php

namespace app\models;

use Yii;
use yii\base\Model;
use app\models\GenF;
use yii\helpers\Url;
use yii\helpers\Json;

use yii\web\YiiAsset;
use yii\bootstrap\BootstrapAsset;

//use yii\jui\Widget;
use yii\jui\Draggable;

class graph_canvas extends Model
{

    private $graph_html = '';
    private $base_id = '';

    private $y_one = 1; //1 - если по оси y единственный ряд значений, то название оси пишется так же в шкале y и в легенде
    //0 - название оси пишется только в легенде, даже когда y имеет один ряд значений

    private $event_set = 0;
    private $width = '500px';
    private $height = '400px';

    function __construct(string $id, $width='500px', $height='400px', $fl_y_one=1){

        $this->base_id = $id;
        $this->y_one = $fl_y_one;
        $this->width = $width;
        $this->height = $height;

        Yii::$app->view->registerCss(
            '#'.$this->base_id.'{border:1px solid grey;width:'.$this->width.';height:'.$this->height.';position:relative;}'.
            '#'.$this->base_id.'_top_part_canvas{width:100%;}'.
            '#'.$this->base_id.'_scale_x_value{width:100%;vertical-align:top;height:80px;}'.
            '#'.$this->base_id.'_scale_y_value{vertical-align:middle;text-align:right;width:80px;}'.
            '#'.$this->base_id.'_legend_for_y{vertical-align:middle;text-align:left;width:250px;}'.
            '#'.$this->base_id.'_base_source_canvas{}'.
            '#'.$this->base_id.'_div_outher{position:relative;}'.
            '#'.$this->base_id.'_div{width:'.$this->width.';height:'.$this->height.';position:relative;}'.
            '#'.$this->base_id.'_settings_button_collapse{position:relative;top:-10px;left:-10px;background-color:#D8D8D8;border-radius:8px;height:30px;width:160px;display:block;padding:5px;font-weight:bold;cursor:pointer;}'.
            '#'.$this->base_id.'_settings{overflow:auto;height:150px;width:350px;display:none;background-color:#D8D8D8;margin:8px;border-radius:8px;padding:3px;position:relative;left:150px;top:-18px;}'.
            '#'.$this->base_id.'_settings > div{display:table-cell;}'.
            '.'.$this->base_id.'_table_div{display:block;}'.
            '.'.$this->base_id.'_table_div > div{display:table-cell;}'
        );

        $this->graph_html = '<div id="'.$this->base_id.'_div_outher">
            <div id="'.$this->base_id.'_base_source_canvas">
                <div class="'.$this->base_id.'_table_div" id="'.$this->base_id.'_top_part_canvas">
                    <div id="'.$this->base_id.'_scale_y_value"></div>
                    <div id="'.$this->base_id.'_div"><canvas id="'.$this->base_id.'" width="'.$this->width.'" height="'.$this->height.'"></canvas></div>
                    <div id="'.$this->base_id.'_legend_for_y"></div>
                </div>
                <div id="'.$this->base_id.'_scale_x_value"></div>
            </div>
            <div style="height:0px;width:0px;">
                <div id="'.$this->base_id.'_settings_button_collapse">&#8616; настройки графика</div>
            </div>
            <div style="height:0px;width:0px;">
                <div id="'.$this->base_id.'_settings">
                    <div id="'.$this->base_id.'_settings_detect_x_y"></div>
                    <div id="'.$this->base_id.'_settings_set_color"></div>
                </div>
            </div>
        </div>';

        $jsCode =<<<JS
        
        var json_data_$this->base_id;

        var canvas_w_$this->base_id = "$this->width";
        var canvas_h_$this->base_id = "$this->height";

        var arr_max_$this->base_id = {};
        var arr_min_$this->base_id = {};
        
        var arr_max_x_$this->base_id = {};
        var arr_min_x_$this->base_id = {};

        var arr_max_y_$this->base_id = {};
        var arr_min_y_$this->base_id = {};

        var y_one_$this->base_id = "$this->y_one";
        
        var canvas_y_$this->base_id = $("#$this->base_id").position().top + $("#$this->base_id").innerHeight();
		var canvas_x_$this->base_id = $("#$this->base_id").position().left;
		
		var checked_x_graph_$this->base_id = {};
		var checked_y_graph_$this->base_id = {};
        
        var get_max_param_$this->base_id = function (json_data, arr_true_key){

            arr_true_key = arr_true_key || false;
            
            var i = 0;
            var j = 0;
            var cur_max = 0;
            var gen_max = 0;
            var cur_key = '';
            var return_arr = {};
            if(arr_true_key === false){
                for (i = 0; i < Object.keys(json_data['head']).length; i++){
                    cur_key = Object.keys(json_data['head'])[i];
                    cur_max = 0;
                    for (j = 0; j < Object.keys(json_data['data']).length; j++){
                        if(json_data['data'][cur_key+'_'+(j+1)] === undefined){break;}
                        
                        if(cur_max < 1*json_data['data'][cur_key+'_'+(j+1)]){
                            cur_max = 1*json_data['data'][cur_key+'_'+(j+1)];
                        }
                        if(gen_max < 1*json_data['data'][cur_key+'_'+(j+1)]){
                            gen_max = 1*json_data['data'][cur_key+'_'+(j+1)];
                        }
                    }
                    return_arr[cur_key] = 1*cur_max;
                }
            }else if (arr_true_key instanceof Array || typeof arr_true_key === 'object'){
                if(arr_true_key instanceof Array){
                    for (i = 0; i < Object.keys(json_data['head']).length; i++){
                        cur_key = Object.keys(json_data['head'])[i];
                        if(arr_true_key.indexOf(cur_key) !== -1){
                            cur_max = 0;
                            for (j = 0; j < Object.keys(json_data['data']).length; j++){
                                if(json_data['data'][cur_key+'_'+(j+1)] === undefined){break;}
                                
                                if(cur_max < 1*json_data['data'][cur_key+'_'+(j+1)]){
                                    cur_max = 1*json_data['data'][cur_key+'_'+(j+1)];
                                }
                                if(gen_max < 1*json_data['data'][cur_key+'_'+(j+1)]){
                                    gen_max = 1*json_data['data'][cur_key+'_'+(j+1)];
                                }
                            }
                            return_arr[cur_key] = 1*cur_max;
                        }
                    }
                }else{
                    for (i = 0; i < Object.keys(json_data['head']).length; i++){
                        cur_key = Object.keys(json_data['head'])[i];
                        if(cur_key in arr_true_key){
                            cur_max = 0;
                            for (j = 0; j < Object.keys(json_data['data']).length; j++){
                                if(json_data['data'][cur_key+'_'+(j+1)] === undefined){break;}
                                
                                if(cur_max < 1*json_data['data'][cur_key+'_'+(j+1)]){
                                    cur_max = 1*json_data['data'][cur_key+'_'+(j+1)];
                                }
                                if(gen_max < 1*json_data['data'][cur_key+'_'+(j+1)]){
                                    gen_max = 1*json_data['data'][cur_key+'_'+(j+1)];
                                }
                            }
                            return_arr[cur_key] = 1*cur_max;
                        }
                    }
                }
            }
            
            return_arr['gen_max'] = 1*gen_max;
            return return_arr;
        };
        
        var get_min_param_$this->base_id = function (json_data, arr_true_key){
            
            arr_true_key = arr_true_key || false;
            
            var i = 0;
            var j = 0;
            var cur_min = 'NaN';
            var gen_min = 'NaN';
            var cur_key = '';
            var return_arr = {};
            if(arr_true_key === false){
                for (i = 0; i < Object.keys(json_data['head']).length; i++){
                    cur_key = Object.keys(json_data['head'])[i];
                    cur_min = 'NaN';
                    for (j = 0; j < Object.keys(json_data['data']).length; j++){
                        if(json_data['data'][cur_key+'_'+(j+1)] === undefined){break;}
                        if(cur_min === 'NaN'){cur_min = 1*json_data['data'][cur_key+'_'+(j+1)];}
                        if(gen_min === 'NaN'){gen_min = 1*json_data['data'][cur_key+'_'+(j+1)];}
                        if(cur_min > 1*json_data['data'][cur_key+'_'+(j+1)]){
                            cur_min = 1*json_data['data'][cur_key+'_'+(j+1)];
                        }
                        if(gen_min > 1*json_data['data'][cur_key+'_'+(j+1)]){
                            gen_min = 1*json_data['data'][cur_key+'_'+(j+1)];
                        }
                    }
                    return_arr[cur_key] = 1*cur_min;
                }
            }else if (arr_true_key instanceof Array || typeof arr_true_key === 'object'){
                if(arr_true_key instanceof Array){
                    for (i = 0; i < Object.keys(json_data['head']).length; i++){
                        cur_key = Object.keys(json_data['head'])[i];
                        if(arr_true_key.indexOf(cur_key) !== -1){
                            cur_min = 'NaN';
                            for (j = 0; j < Object.keys(json_data['data']).length; j++){
                                if(json_data['data'][cur_key+'_'+(j+1)] === undefined){break;}
                                if(cur_min === 'NaN'){cur_min = 1*json_data['data'][cur_key+'_'+(j+1)];}
                                if(gen_min === 'NaN'){gen_min = 1*json_data['data'][cur_key+'_'+(j+1)];}
                                if(cur_min > 1*json_data['data'][cur_key+'_'+(j+1)]){
                                    cur_min = 1*json_data['data'][cur_key+'_'+(j+1)];
                                }
                                if(gen_min > 1*json_data['data'][cur_key+'_'+(j+1)]){
                                    gen_min = 1*json_data['data'][cur_key+'_'+(j+1)];
                                }
                            }
                            return_arr[cur_key] = 1*cur_min;
                        }
                    }
                }else{
                    for (i = 0; i < Object.keys(json_data['head']).length; i++){
                        cur_key = Object.keys(json_data['head'])[i];
                        if(cur_key in arr_true_key){
                            cur_min = 'NaN';
                            for (j = 0; j < Object.keys(json_data['data']).length; j++){
                                if(json_data['data'][cur_key+'_'+(j+1)] === undefined){break;}
                                if(cur_min === 'NaN'){cur_min = 1*json_data['data'][cur_key+'_'+(j+1)];}
                                if(gen_min === 'NaN'){gen_min = 1*json_data['data'][cur_key+'_'+(j+1)];}
                                if(cur_min > 1*json_data['data'][cur_key+'_'+(j+1)]){
                                    cur_min = 1*json_data['data'][cur_key+'_'+(j+1)];
                                }
                                if(gen_min > 1*json_data['data'][cur_key+'_'+(j+1)]){
                                    gen_min = 1*json_data['data'][cur_key+'_'+(j+1)];
                                }
                            }
                            return_arr[cur_key] = 1*cur_min;
                        }
                    }
                }
            }
            return_arr['gen_min'] = 1*gen_min;
            return return_arr;
        };
        
        var draw_select_paramscale_map_graph_$this->base_id = function (json_data){
            
            if(Object.keys(checked_x_graph_$this->base_id).length === 0 && checked_x_graph_$this->base_id.constructor === Object && Object.keys(checked_y_graph_$this->base_id).length === 0 && checked_y_graph_$this->base_id.constructor === Object){
                $("#$this->base_id"+"_settings_detect_x_y").html('<div style="display:table;">' +
                 '<div style="display:table-cell;width:30px;text-align:center;">X</div>' +
                  '<div style="display:table-cell;width:30px;text-align:center;">Y</div>' +
                  '<div style="display:table-cell;width:120px;text-align:center;">COLOR LINE</div>' +
                  '<div style="display:table-cell;width:30px;text-align:center;"></div>' +
                  '<div style="display:table-cell;width:30px;text-align:center;">NAME</div>' +
                  
                   '</div>');

                for (var key in json_data['head']){
                    $("#$this->base_id"+"_settings_detect_x_y").append('<div style="display:table;">' +
                     '<div style="display:table-cell;width:30px;height:45px;text-align:center;"><input type="checkbox" this_value="'+key+'" id="'+key+'_x_'+'$this->base_id'+'" name="'+json_data['head'][key]+'" class="'+'$this->base_id'+'_paramscale_map_graph_x"/></div>' +
                      '<div style="display:table-cell;width:30px;height:45px;text-align:center;"><input type="checkbox" this_value="'+key+'" id="'+key+'_y_'+'$this->base_id'+'" name="'+json_data['head'][key]+'" class="'+'$this->base_id'+'_paramscale_map_graph_y"/></div>' +
                      '<div id="'+"$this->base_id"+'_'+key+'_color_code" style="display:table-cell;margin:3px;height:45px;background-color:rgb(' + json_data['color'][key] + ');width:20px;height:8px;"></div>'+
                      '<div style="display:table-cell;text-align:left;height:45px;padding:3px;"><input style="width:110px;" class="'+"$this->base_id"+'_changer_color" key="'+key+'" value="rgb(' + json_data['color'][key] + ')" /></div>'+
                      '<div style="display:table-cell;text-align:left;height:45px;"><div style="display:block;width:110px;overflow:auto;">' + json_data['head'][key] + '</div></div></div>'

                       );
                }
            }
        
        };
        
        $(document).on("click", "#$this->base_id"+"_settings_button_collapse", function(){
            
            if($("#$this->base_id"+"_settings").css('display') === 'none'){
                if($("#$this->base_id"+"_settings_detect_x_y").html() !== ''){
                    $("#$this->base_id"+"_settings").css('height', 0);
                    $("#$this->base_id"+"_settings").css('display', 'block');
                    $("#$this->base_id"+"_settings").animate({height: 150}, 300,function(){
                    });
                }
            }else{
                $("#$this->base_id"+"_settings").animate({height: 0}, 300,function(){
                    $("#$this->base_id"+"_settings").css('display', 'none');
                });
            }
            
        });
        
        $(document).on("click", ".$this->base_id"+"_paramscale_map_graph_x", function(){

        	var this_value = $(this).attr('this_value');

            checked_x_graph_$this->base_id = {};
            if($(this).prop('checked') === true){
                checked_x_graph_$this->base_id[this_value] = $(this).attr('name');
            }else{
                delete checked_x_graph_$this->base_id[this_value];
            }
            $(".$this->base_id"+"_paramscale_map_graph_x").each(function (i, el){
                if($(el).prop('checked') === true && $(el).attr('this_value') !== this_value){
                    $(el).prop('checked', false);
                }
            });
            $(".$this->base_id"+"_paramscale_map_graph_y").each(function (i, el){
                if($(el).prop('checked') === true && $(el).attr('this_value') === this_value){
                    $(el).prop('checked', false);
                    delete checked_y_graph_$this->base_id[this_value];
                }
            });
            
            $("#$this->base_id").trigger('change_specification_graph_'+'$this->base_id', [json_data_$this->base_id]);
            
        });

        $(document).on("click", ".$this->base_id"+"_paramscale_map_graph_y", function(){

            var this_value = $(this).attr('this_value');
            
            if($(this).prop('checked') === true){
                checked_y_graph_$this->base_id[this_value] = $(this).attr('name');
            }else{
                delete checked_y_graph_$this->base_id[this_value];
            }
            $(".$this->base_id"+"_paramscale_map_graph_x").each(function (i, el){
                if($(el).prop('checked') === true && $(el).attr('this_value') === this_value){
                    $(el).prop('checked', false);
                    delete checked_x_graph_$this->base_id[this_value];
                }
            });

            $("#$this->base_id").trigger('change_specification_graph_'+'$this->base_id', [json_data_$this->base_id]);
            
        });
        
        $("#$this->base_id").on('change_specification_graph_'+'$this->base_id', function( event, jsondat) {

            draw_graph_$this->base_id(jsondat, 'scale_specification');
            
        });

        $(document).on("change", ".$this->base_id"+"_changer_color", function(){
            var in_key = $(this).attr('key');
            var cur_value = $(this).val();
            //изменение цвета внутри настроек
            $("#$this->base_id"+'_'+in_key+"_color_code").css('background-color', $(this).val());
            //изменение цвета внутри легенды
            $("#$this->base_id"+"_"+in_key+"_legend_color_code").css('background-color', $(this).val());
            //изменение цвета внутри json данных
            var color_value = cur_value.substring(4);
            color_value = color_value.substring(0, color_value.length - 1);
            json_data_$this->base_id['color'][in_key] = color_value;
            //перерисовка самого графика
            draw_graph_$this->base_id(json_data_$this->base_id, 'scale_specification');
            
        });
        
        var pre_draw_$this->base_id = function (json_data, spec){
    
            spec = spec || '';
            
            if(Object.keys(json_data['head']).length < 2){
                alert('Кол-во наблюдаемых объектов должно быть более одного');
			    return false;
            }
            
            arr_max_$this->base_id = {};
            arr_max_$this->base_id = get_max_param_$this->base_id(json_data);
            
            arr_min_$this->base_id = {};
            arr_min_$this->base_id = get_min_param_$this->base_id(json_data);
            
            canvas_y_$this->base_id = $("#$this->base_id").position().top + $("#$this->base_id").height();
		    canvas_x_$this->base_id = $("#$this->base_id").position().left;

		    if(spec !== 'scale_specification'){
		        checked_x_graph_$this->base_id = {};
                checked_y_graph_$this->base_id = {};
		        draw_select_paramscale_map_graph_$this->base_id(json_data);
		    }
		    
            if(Object.keys(checked_x_graph_$this->base_id).length === 0 && checked_x_graph_$this->base_id.constructor === Object && Object.keys(checked_y_graph_$this->base_id).length === 0 && checked_y_graph_$this->base_id.constructor === Object){
                var h = 0;
                for (var key in json_data['head']){
                    if(h === 0){
                        checked_x_graph_$this->base_id[key] = json_data['head'][key];
                        $("#"+key+"_x_"+"$this->base_id").prop('checked', true);
                    }else{
                        checked_y_graph_$this->base_id[key] = json_data['head'][key];
                        $("#"+key+"_y_"+"$this->base_id").prop('checked', true);
                    }
                    h = h + 1;
                }
            }

            arr_max_x_$this->base_id = {};
            arr_max_x_$this->base_id = get_max_param_$this->base_id(json_data, checked_x_graph_$this->base_id);
            arr_min_x_$this->base_id = {};
            arr_min_x_$this->base_id = get_min_param_$this->base_id(json_data, checked_x_graph_$this->base_id);
            
            arr_max_y_$this->base_id = {};
            arr_max_y_$this->base_id = get_max_param_$this->base_id(json_data, checked_y_graph_$this->base_id);
            arr_min_y_$this->base_id = {};
            arr_min_y_$this->base_id = get_min_param_$this->base_id(json_data, checked_y_graph_$this->base_id);
            
            return true;
            
        };

        var x_coord_$this->base_id = function(x, min_x, max_x, c_width){
            if((1*max_x - 1*min_x) === 0){
                return 0;
            }
            return (c_width * (1*x - 1*min_x))/(1*max_x - 1*min_x);
        };
        var y_coord_$this->base_id = function(y, min_gen, max_gen, c_height){
            if((1*max_gen - 1*min_gen) === 0){
                return 1*c_height;
            }
            return 1*c_height - (c_height * (1*y - 1*min_gen))/(1*max_gen - 1*min_gen);
        };
        
        var draw_legend__$this->base_id = function(json_data, checked_y, selector_place_legend){
            
            if($(".$this->base_id"+"_class_legend").length !== 0){
                $(".$this->base_id"+"_class_legend").remove();
            }
            
            selector_place_legend.append('<div style="background-color:#D8D8D8;margin:8px;border-radius:8px;padding:3px;display:block;overflow:auto;width:250px;height:'+canvas_h_$this->base_id+';" id="'+"$this->base_id"+'_id_area_legend" class="' + "$this->base_id" + '_class_legend '+"$this->base_id"+'_class_graph_element"></div>');

            for(var key in json_data['head']){
                if(checked_y[key]){
                    $("#$this->base_id"+"_id_area_legend").append('<div style="display:block;padding:3px;" class="' + "$this->base_id" + '_class_legend '+"$this->base_id"+'_class_graph_element"><div style="display:table-cell;"><div id="'+"$this->base_id"+'_'+key+'_legend_color_code" style="display:block;padding:3px;width:30px;background-color:rgb('+json_data['color'][key]+')"></div></div><div style="display:table-cell;text-align:left;padding:3px;">'+json_data['head'][key]+'</div></div>');
                }
            }

        };
        
        var draw_point__$this->base_id = function(x, y, xReal, yReal, rgb, selector){
            var dop_style = 'position:absolute;border-radius:6px;width:6px;height:6px;cursor:pointer;border:1px solid #2C2C2C; background-color: '+rgb+';';
            selector.parent().append('<div class="'+"$this->base_id"+'_point_graph '+"$this->base_id"+'_class_graph_element" os_x="'+xReal+'" os_y="'+yReal+'" rgb_color="'+rgb+'" style="z-index:'+selector.css('zIndex')+';top:'+(y + selector.position().top - 3)+'px;left:'+(x + selector.position().left - 3)+'px;'+dop_style+'"></div>');
        };
        
        $(document).on("click", ".$this->base_id" + "_point_graph", function(){
            if($(this).html() === '' && $(this).width() >= 4 && $(this).width() <= 8){
                $(this).css('z-index','3');
                $(this).css('overflow','auto');
                $(this).animate({
                    width: "+=80",
                    height: "+=50"
                }, 500, function() {
                    $(this).html('<div style="background-color:white;font-size:12px;height:80%;width:80%;top:8%;left:8%;position:relative;border-radius:3px;">Y:&#160;<span style="font-weight:bold;">'+ (1*$(this).attr('os_y')).toFixed(2) +'</span><br/>X:&#160;<span style="font-weight:bold;">'+ (1*$(this).attr('os_x')).toFixed(2) +'</span></div>');
                });
            }
        });
	    $(document).on("mouseenter", ".$this->base_id" + "_point_graph", function(){
			$(this).css('box-shadow', '0px 0px 0px 3px #F58E47');
			if($(this).html() !== ''){
				$(this).html('');
				$(this).animate({
					width: "-=80",
					height: "-=50"
				}, 500, function() {
					$(this).html('');
					$(this).css('z-index','2');
				});
			}
		});
	    $(document).on("mouseleave", ".$this->base_id" + "_point_graph", function(){
			$(this).css('box-shadow', '');			
			if($(this).html() !== ''){
				$(this).html('');
				$(this).animate({
					width: "-=80",
					height: "-=50"
				}, 500, function() {
					$(this).css('z-index','2');
				});
			}
	    });
        
        var draw_scale__y_$this->base_id = function(json_data, checked_y, val_min, val_max, selector_place_scale_y){
            
            if($("#$this->base_id"+"_scale__y").length !== 0){
                $("#$this->base_id"+"_scale__y").remove();
            }
            
            var scale_y_width = '80px';
            var scale_y_width_value = 80;
            //ширина канвы шкалы X расчетная, для расчета координат делений
            var scale_y_height_true = 1*$("#$this->base_id").innerHeight() + 2;
            
            selector_place_scale_y.append('<canvas id="'+"$this->base_id"+'_scale__y" class="'+"$this->base_id"+'_class_graph_element" width="' + scale_y_width + '" height="' + scale_y_height_true + 'px" style="position:relative;width:' + scale_y_width + ';height:'+scale_y_height_true+'px;top:-5px;left:0px;"></canvas>');
            
            var cur_scale_y_$this->base_id = $("#$this->base_id"+"_scale__y")[0];
            var canvas_scale_y_$this->base_id = cur_scale_y_$this->base_id.getContext("2d");
            
            var count_periods = Object.keys(json_data['data']).length / Object.keys(json_data['head']).length;
            
            //текущий интервал шкалы в пикселях
            var cur_px_interval = scale_y_height_true / count_periods;
            var cur_interval;
            var denominator;

            if(cur_px_interval < 19){
                // если текущий интервал меньше 19px, тогда информация в шкале не поместится, поэтому необходимо задать делитель равным количеству наблюдений, который будет считаться по формуле scale_y_height_true / 19
                denominator = scale_y_height_true / 19;
            }else{
                // если текущий интервал больше либо равен 19px, тогда информация в шкале поместится, задать делитель равным количеству наблюдений count_periods минус 1; 
                denominator = count_periods - 1;
            }
            
            cur_interval = (val_max - val_min)/denominator;
            
            if(cur_interval == 0){cur_interval = 1;}
            
            canvas_scale_y_$this->base_id.beginPath();
            canvas_scale_y_$this->base_id.moveTo(0, 0);
            
            var cur_i;
            var view_i = '';
            var corcoef_length_val = 0;
            
            for (var i = val_min; i < val_max + 1; i = i + cur_interval){
                
                cur_i = y_coord_$this->base_id(i, val_min, val_max, scale_y_height_true);

                if(1*i === 1*val_min){
                    // для полной видимости линии шкалы первого значения по оси Y (ее длина 1px)
                    canvas_scale_y_$this->base_id.moveTo(scale_y_width_value, cur_i-1);
                    canvas_scale_y_$this->base_id.lineTo(scale_y_width_value - 8, cur_i-1);
                }else if(1*i + 1*cur_interval >= 1*val_max + 1*cur_interval){
                    // для полной видимости линии шкалы последнего значения по оси Y (ее длина 1px)
                    canvas_scale_y_$this->base_id.moveTo(scale_y_width_value, cur_i+1);
                    canvas_scale_y_$this->base_id.lineTo(scale_y_width_value - 8, cur_i+1);
                }else{
                    canvas_scale_y_$this->base_id.moveTo(scale_y_width_value, cur_i);
                    canvas_scale_y_$this->base_id.lineTo(scale_y_width_value - 8, cur_i);
                }
                
                if((''+i).length > 8){
                    view_i = (i+'').substr(0, 8) + '...';
                    corcoef_length_val = -6;
                } else {
                    corcoef_length_val = Math.round(55 - (57*(''+i).length/8) - ((7/5)*(7-(''+i).length)));
                    view_i = i;
                }

                canvas_scale_y_$this->base_id.save();

                if(1*i === 1*val_min){
                    canvas_scale_y_$this->base_id.translate(1*scale_y_width_value-55, cur_i - 53);
                }else{
                    canvas_scale_y_$this->base_id.translate(1*scale_y_width_value-55, cur_i - 41);
                }

                canvas_scale_y_$this->base_id.fillText(view_i , corcoef_length_val, 50);
                canvas_scale_y_$this->base_id.restore();
                
            }

            if(y_one_$this->base_id === '1' && Object.keys(checked_y).length === 1){
                canvas_scale_y_$this->base_id.save();
                canvas_scale_y_$this->base_id.rotate(-Math.PI / 2);
                canvas_scale_y_$this->base_id.textAlign = "center";
                canvas_scale_y_$this->base_id.fillText(checked_y[Object.keys(checked_y)[0]], -1*scale_y_height_true/2, 10);
                canvas_scale_y_$this->base_id.restore();
            }
            
            canvas_scale_y_$this->base_id.lineWidth = 1;
            canvas_scale_y_$this->base_id.stroke();
            
	    };

        var draw_scale__x_$this->base_id = function (json_data, val_min, val_max, selector_place_scale_x, label){
            
            if($("#$this->base_id"+"_scale__x").length !== 0){
                $("#$this->base_id"+"_scale__x").remove();
            }
		    
            var scale_x_height = '80px';
            var scale_x_height_value = 80;
            //ширина канвы шкалы X расчетная, для расчета координат делений 
            var scale_x_width_true = 1*$("#$this->base_id").innerWidth() + 2;
            
    		selector_place_scale_x.append('<canvas id="'+"$this->base_id"+'_scale__x" class="'+"$this->base_id"+'_class_graph_element" width="' + scale_x_width_true + 'px" height="' + scale_x_height + '" style="position:relative;width:' + scale_x_width_true + 'px;height:'+scale_x_height+';top:-18px;left:80px;"></canvas>');
            
		    var cur_scale_x_$this->base_id = $("#$this->base_id"+"_scale__x")[0];
            var canvas_scale_x_$this->base_id = cur_scale_x_$this->base_id.getContext("2d");
            
            var count_periods = Object.keys(json_data['data']).length / Object.keys(json_data['head']).length;
            
            //текущий интервал шкалы в пикселях
            var cur_px_interval = scale_x_width_true / count_periods;
            var cur_interval;
            var denominator;

            if(cur_px_interval < 19){
                // если текущий интервал меньше 19px, тогда информация в шкале не поместится, поэтому необходимо задать делитель равным количеству наблюдений, который будет считаться по формуле scale_x_width_true / 19
                denominator = scale_x_width_true / 19;
            }else{
                // если текущий интервал больше либо равен 19px, тогда информация в шкале поместится, задать делитель равным количеству наблюдений count_periods минус 1; 
                denominator = count_periods - 1;
            }
            
            cur_interval = (val_max - val_min)/denominator;
            
            if(cur_interval == 0){cur_interval = 1;}
            
            canvas_scale_x_$this->base_id.beginPath();
            canvas_scale_x_$this->base_id.moveTo(0, 0);
            
            var cur_i;
            var view_i = '';
            var corcoef_length_val = 0;
            
            for (var i = val_min; i < val_max + cur_interval; i = i + cur_interval){
                
                cur_i = x_coord_$this->base_id(i, val_min, val_max, scale_x_width_true);

                if(1*i === 1*val_min){
                    // для полной видимости линии шкалы первого значения по оси X (ее длина 1px)
                    canvas_scale_x_$this->base_id.moveTo(cur_i + 1, 0);
                    canvas_scale_x_$this->base_id.lineTo(cur_i + 1, 8);
                }else if(1*i + 1*cur_interval >= 1*val_max + 1*cur_interval){
                    // для полной видимости линии шкалы последнего значения по оси X (ее длина 1px)
                    canvas_scale_x_$this->base_id.moveTo(cur_i - 1, 0);
                    canvas_scale_x_$this->base_id.lineTo(cur_i - 1, 8);
                }else{
                    canvas_scale_x_$this->base_id.moveTo(cur_i, 0);
                    canvas_scale_x_$this->base_id.lineTo(cur_i, 8);
                }
                
                if((''+i).length > 8){
                    view_i = (i+'').substr(0, 8) + '...';
                    corcoef_length_val = -6;
                } else {
                    corcoef_length_val = Math.round(55 - (55*(''+i).length/7) - (2*(7-(''+i).length)));
                    view_i = i;
                }
                
                canvas_scale_x_$this->base_id.save();
                
                var max_colsymb_x = 3;
                if((''+val_min).indexOf('.')===-1 && (''+val_max).indexOf('.')===-1 && (''+cur_interval).indexOf('.')===-1){
                    max_colsymb_x = (''+val_max).length;
                }
                
                if(max_colsymb_x < 3){
                    if(1*i === 1*val_min){
                        // для полной видимости цифры шкалы первого значения по оси X
                        canvas_scale_x_$this->base_id.translate(cur_i-34, -30);
                    }else if(1*i + 1*cur_interval >= 1*val_max + 1*cur_interval){
                        // для полной видимости цифры шкалы последнего значения по оси X
                        canvas_scale_x_$this->base_id.translate(cur_i-41, -30);
                    }else{
                        canvas_scale_x_$this->base_id.translate(cur_i-36, -30);
                    }
                }else{
                    if(1*i + 1*cur_interval >= 1*val_max + 1*cur_interval){
                        canvas_scale_x_$this->base_id.translate(cur_i-50, 50.4 + (''+i).length * 0.2);
                        canvas_scale_x_$this->base_id.rotate(-0.5*Math.PI);
                    }else{
                        canvas_scale_x_$this->base_id.translate(cur_i-41, 50.4 + (''+i).length * 0.2);
                        canvas_scale_x_$this->base_id.rotate(-0.5*Math.PI);
                    }
                }
                
                canvas_scale_x_$this->base_id.fillText(view_i , corcoef_length_val, 50);
                canvas_scale_x_$this->base_id.restore();

            }
            
            canvas_scale_x_$this->base_id.textAlign = "center";
            canvas_scale_x_$this->base_id.fillText(label, scale_x_width_true/2, 70);
            
            canvas_scale_x_$this->base_id.lineWidth = 1;
            canvas_scale_x_$this->base_id.stroke();
            
	    };

        
        var clear_graph_$this->base_id = function(ctx, spec){
            spec = spec || '';
            ctx.clearRect(0, 0, $("#$this->base_id").innerWidth()+21, $("#$this->base_id").innerHeight()+21);
            if(spec !== 'scale_specification'){
                $("#$this->base_id"+"_settings_detect_x_y").html('');
            }
            
            $(".$this->base_id"+"_class_graph_element").remove();
        };
        
        var draw_graph_$this->base_id = function (json_data, spec){
            spec = spec || '';
            json_data_$this->base_id = json_data;
            
            if(typeof json_data === 'string'){
                json_data = JSON.parse(json_data);
            }else if(typeof json_data === 'object'){
            }else{
                alert('Необходимо ввести json данные в формате string или object');
                return false;
            }

            var cur_canvas_$this->base_id = $("#$this->base_id")[0];
            var canvas_draw_$this->base_id = cur_canvas_$this->base_id.getContext("2d");
            clear_graph_$this->base_id(($("#$this->base_id")[0]).getContext("2d"), spec);

            var prescreen = pre_draw_$this->base_id(json_data, spec);
            
            var sortable = [];
            for (var vehicle in json_data['data']) {sortable.push([vehicle, json_data['data'][vehicle]]);}
            sortable.sort(function(a, b) {return a[1] - b[1]});
            
            var sort_cur;
            var sort_index;
            var sort_name;
            var cur_x;
            var cur_y;
            
            var col_line = 0;

            for(var key1 in arr_max_y_$this->base_id){
                canvas_draw_$this->base_id.beginPath();
                if(key1 != 'gen_max'){

                    if(col_line > 0){
                        for (var key2 in sortable){
                            sort_cur = sortable[key2][0];
                            sort_name = sort_cur.substr(0, sort_cur.lastIndexOf('_'));
                            sort_index = sort_cur.substr(sort_cur.lastIndexOf('_')+('_').length);
                            if(Object.keys(arr_max_x_$this->base_id).indexOf(sort_name) !== -1){
                                
                                if(isNaN(parseFloat(1*sortable[key2][1]))){
                                    alert('Введены некорректные данные!');
                                    //local_clear_graph(selector);
                                    return;
                                }
                                if(isNaN(parseFloat(1*json_data['data'][key1+'_'+sort_index]))){
                                    alert('Введены некорректные данные!');
                                    //local_clear_graph(selector);
                                    return;
                                }
                                
                                cur_x = x_coord_$this->base_id(1*sortable[key2][1], arr_min_x_$this->base_id['gen_min'], arr_max_x_$this->base_id['gen_max'], $("#$this->base_id").innerWidth());
                                cur_y = y_coord_$this->base_id(1*json_data['data'][key1+'_'+sort_index], arr_min_y_$this->base_id['gen_min'], arr_max_y_$this->base_id['gen_max'], $("#$this->base_id").innerHeight());
                                canvas_draw_$this->base_id.moveTo(cur_x, cur_y);
                                break;
                            }
                        }
                    }
                    
                    canvas_draw_$this->base_id.strokeStyle = 'rgb('+json_data['color'][key1]+')';
                    
                    for (var key2 in sortable){
                        sort_cur = sortable[key2][0];
                        sort_name = sort_cur.substr(0, sort_cur.lastIndexOf('_'));
                        sort_index = sort_cur.substr(sort_cur.lastIndexOf('_')+('_').length);

                        if(Object.keys(arr_max_x_$this->base_id).indexOf(sort_name) !== -1){
    
                            if(isNaN(parseFloat(1*sortable[key2][1]))){
                                alert('Введены некорректные данные!');
                                //local_clear_graph(selector);
                                return;
                            }
                            if(isNaN(parseFloat(1*json_data['data'][key1+'_'+sort_index]))){
                                alert('Введены некорректные данные!');
                                //local_clear_graph(selector);
                                return;
                            }
                            
                            cur_x = x_coord_$this->base_id(1*sortable[key2][1], arr_min_x_$this->base_id['gen_min'], arr_max_x_$this->base_id['gen_max'], $("#$this->base_id").innerWidth());
                            cur_y = y_coord_$this->base_id(1*json_data['data'][key1+'_'+sort_index], arr_min_y_$this->base_id['gen_min'], arr_max_y_$this->base_id['gen_max'], $("#$this->base_id").innerHeight());
                            
                            canvas_draw_$this->base_id.lineTo(cur_x, cur_y);
                            canvas_draw_$this->base_id.lineWidth = 1;
                            draw_point__$this->base_id(cur_x, cur_y, 1*sortable[key2][1], 1*json_data['data'][key1+'_'+sort_index], 'rgb('+json_data['color'][key1]+')', $("#$this->base_id"));
                        }
                    }
                    col_line = col_line + 1;  
                }
                canvas_draw_$this->base_id.stroke();
            }
            
            draw_scale__x_$this->base_id(json_data, arr_min_x_$this->base_id['gen_min'], arr_max_x_$this->base_id['gen_max'], $("#$this->base_id"+"_scale_x_value"), json_data['head'][Object.keys(arr_max_x_$this->base_id)[0]]);
            draw_scale__y_$this->base_id(json_data, checked_y_graph_$this->base_id, arr_min_y_$this->base_id['gen_min'], arr_max_y_$this->base_id['gen_max'], $("#$this->base_id"+"_scale_y_value"));
            draw_legend__$this->base_id(json_data, checked_y_graph_$this->base_id, $("#$this->base_id"+"_legend_for_y"));
            
        }
JS;
        Yii::$app->view->registerJs($jsCode);

    }

    public function set_graph($draggable = 0){
        if($this->event_set === 0 && $draggable === 1){
            Draggable::begin([
                'options' => ['id' => $this->base_id.'_draggable'],
            ]);
            echo $this->graph_html;
            Draggable::end();
            $this->event_set = 1;
        }else if($this->event_set === 0 && $draggable === 0){
            echo '<div id="'.$this->base_id.'_draggable">'.$this->graph_html.'</div>';
            $this->event_set = 1;
        }
    }

    public function set_dynamic_data($selector_trigger, $name_trigger){

        $jqueryCode =<<<JS
        
        if("$selector_trigger" === "document"){
            $(document).on("$name_trigger", function(event, json_data){
                if(json_data === undefined || json_data === '' || json_data === [] || json_data === {}){
                    alert('Триггер должен возвращать данные в формате json!');
                }else{
                    draw_graph_$this->base_id(json_data);
                }
            }); 
        }else if("$selector_trigger" === "window"){
            $(window).on("$name_trigger", function(event, json_data){
                if(json_data === undefined || json_data === '' || json_data === [] || json_data === {}){
                    alert('Триггер должен возвращать данные в формате json!');
                }else{
                    draw_graph_$this->base_id(json_data);
                }
            });
        }else{
            $("$selector_trigger").on("$name_trigger", function(event, json_data){
                if(json_data === undefined || json_data === '' || json_data === [] || json_data === {}){
                    alert('Триггер должен возвращать данные в формате json!');
                }else{
                    draw_graph_$this->base_id(json_data);
                }
            });
        }
        
JS;

        Yii::$app->view->registerJs($jqueryCode);

    }

}