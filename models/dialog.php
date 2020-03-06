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

class dialog extends Model
{

    private $dialog_html = '';
    private $base_id = '';
    private $event_set = 0;
    private $selector_call = '';

    function __construct(string $id){

        $this->base_id = $id;

        Yii::$app->view->registerCss(
            '#'.$id.'{position:absolute;z-index:10001;padding:8px;}'.
            '#'.$id.'_draggable{cursor:move;position:absolute;height:0px;width:0px;z-index:10000;display:none;}'.
            '#'.$id.'_controll_btn{display:table;float:right;}'.
            '#'.$id.'_block_controll{display:table;width:100%;}'.
            '.'.$id.'_controll_btn_dialog_cell{padding:3px;display:table-cell;}'.
            '#'.$id.'_controll_btn_full_window{font-size:12px;width:20px;height:20px;padding:0px;cursor:pointer;}'.
            '#'.$id.'_controll_btn_collapse{font-size:12px;width:20px;height:20px;padding:0px;cursor:pointer;}'.
            '#'.$id.'_controll_btn_x_close{font-size:12px;width:20px;height:20px;padding:0px;cursor:pointer;}'.
            '#'.$id.'_source_area{display:table;}'

        );

        $this->dialog_html = '<div id="'.$id.'">'
                .'<div id="'.$id.'_block_controll"><div id="'.$id.'_controll_btn">'
                    .'<div id="'.$id.'_controll_btn_cell_full_window" class="'.$id.'_controll_btn_dialog_cell">'
                        .'<input id="'.$id.'_controll_btn_full_window" type="button" value="&#9744;"/>'
                    .'</div>'
                    .'<div id="'.$id.'_controll_btn_cell_collapse" class="'.$id.'_controll_btn_dialog_cell">'
                        .'<input id="'.$id.'_controll_btn_collapse" type="button" value="_"/>'
                    .'</div>'
                    .'<div id="'.$id.'_controll_btn_cell_x_close" class="'.$id.'_controll_btn_dialog_cell">'
                        .'<input id="'.$id.'_controll_btn_x_close" type="button" value="&#9747;"/>'
                    .'</div>'
                .'</div></div>'
                .'<div id="'.$id.'_source_area">';
                //.'</div>'
            //.'</div>';
    }

    public function set_dialog($draggable = 1){
        if($this->event_set === 0 && $draggable === 1){
            $this->dialog_html = $this->dialog_html.'</div></div>';
            Draggable::begin([
                'options' => ['id' => $this->base_id.'_draggable'],
            ]);
            echo $this->dialog_html;
            Draggable::end();
            $this->event_set = 1;
        }else if($this->event_set === 0 && $draggable === 0){
            $this->dialog_html = $this->dialog_html.'</div></div>';
            echo '<div id="'.$this->base_id.'_draggable">'.$this->dialog_html.'</div>';
            $this->event_set = 1;
        }

        $jquerySourceDefault = <<<JS
            
            $("#"+"$this->base_id"+"_controll_btn_x_close").click(function(){
                
                $("#$this->base_id").trigger("pre_x_close_"+"$this->base_id");
                
                $("#"+"$this->base_id"+"_draggable").hide();
                $("#"+"$this->base_id").removeAttr('collapse_dialog');
                if($("#"+"$this->base_id").attr('pre_overflow') !== undefined){
                    $("#"+"$this->base_id").css('overflow', $("#"+"$this->base_id").attr('pre_overflow'));
                }
                if($("#"+"$this->base_id").attr('pre_height') !== undefined){
                    $("#"+"$this->base_id").css('height', 1*$("#"+"$this->base_id").attr('pre_height'));
                }
                
                $("#$this->base_id").trigger("past_x_close_"+"$this->base_id");
                
            });

            $("#"+"$this->base_id"+"_controll_btn_collapse").click(function(){
                if($("#"+"$this->base_id").attr('collapse_dialog') !== 'true'){
                    $("#"+"$this->base_id").attr('collapse_dialog', 'true');
                    $("#"+"$this->base_id").attr('pre_top', $("#"+"$this->base_id").position().top);
                    $("#"+"$this->base_id").attr('pre_left', $("#"+"$this->base_id").position().left);
                    $("#"+"$this->base_id").attr('pre_height', $("#"+"$this->base_id").innerHeight());
                    $("#"+"$this->base_id").attr('pre_overflow', $("#"+"$this->base_id").css('overflow'));
                    var cur_left_document = -1*(1*$("#"+"$this->base_id")[0].getClientRects()[0].left - 1*$("#"+"$this->base_id").position().left);
                    var cur_top_document = -1*(1*$("#"+"$this->base_id")[0].getClientRects()[0].top - 1*$("#"+"$this->base_id").position().top) + (1*$(window).innerHeight()) - 38;
                    $("#"+"$this->base_id").animate({left: cur_left_document, top: cur_top_document, height: 38}, 300,function(){ 
                        $("#"+"$this->base_id").css('overflow', 'hidden');
                        if("$draggable" === '1'){
                            $("#"+"$this->base_id"+"_draggable").draggable( 'disable' );
                        }
                    });
                }else{
                    $("#"+"$this->base_id").animate({
                        left: 1*$("#"+"$this->base_id").attr('pre_left'),
                        top: 1*$("#"+"$this->base_id").attr('pre_top'),
                        height: 1*$("#"+"$this->base_id").attr('pre_height')
                    }, 300,function(){ 
                        $("#"+"$this->base_id").removeAttr('collapse_dialog');
                        if("$draggable" === '1'){
                            $("#"+"$this->base_id"+"_draggable").draggable( 'enable' );
                        }
                        if($("#"+"$this->base_id").attr('pre_overflow') !== undefined){
                            $("#"+"$this->base_id").css('overflow', $("#"+"$this->base_id").attr('pre_overflow'));
                        }
                    });
                }
                
                $(window).resize(function() {
                    if($("#"+"$this->base_id").attr('collapse_dialog') === 'true'){
                        var cur_left_document = -1*(1*$("#"+"$this->base_id")[0].getClientRects()[0].left - 1*$("#"+"$this->base_id").position().left);
                        var cur_top_document = -1*(1*$("#"+"$this->base_id")[0].getClientRects()[0].top - 1*$("#"+"$this->base_id").position().top) + (1*$(window).innerHeight()) - 38;
                        $("#"+"$this->base_id").css('left', cur_left_document);
                        $("#"+"$this->base_id").css('top', cur_top_document);
                    }
                });
            });
            
            $("#"+"$this->base_id"+"_controll_btn_full_window").click(function(){
                if($("#"+"$this->base_id").attr('full_dialog') !== 'true'){
                    $("#"+"$this->base_id").attr('full_dialog', 'true');
                    $("#"+"$this->base_id").attr('pre_height', $("#"+"$this->base_id").innerHeight());
                    $("#"+"$this->base_id").attr('pre_width', $("#"+"$this->base_id").innerWidth());
                    $("#"+"$this->base_id").attr('pre_top', $("#"+"$this->base_id").position().top);
                    $("#"+"$this->base_id").attr('pre_left', $("#"+"$this->base_id").position().left);
                    
                    var full_left_document = -1*(1*$("#"+"$this->base_id")[0].getClientRects()[0].left - 1*$("#"+"$this->base_id").position().left);
                    var full_top_document = -1*(1*$("#"+"$this->base_id")[0].getClientRects()[0].top - 1*$("#"+"$this->base_id").position().top);
                    
                    $("#"+"$this->base_id").css('height', $(window).innerHeight());
                    $("#"+"$this->base_id").css('width', $(window).innerWidth());
                    
                    $("#"+"$this->base_id").css('left', full_left_document);
                    $("#"+"$this->base_id").css('top', full_top_document);
                }else{
                    $("#"+"$this->base_id").removeAttr('full_dialog');
                    
                    $("#"+"$this->base_id").css('top', 1*$("#"+"$this->base_id").attr('pre_top'));
                    $("#"+"$this->base_id").css('left', 1*$("#"+"$this->base_id").attr('pre_left'));
                    $("#"+"$this->base_id").css('height', 1*$("#"+"$this->base_id").attr('pre_height'));
                    $("#"+"$this->base_id").css('width', 1*$("#"+"$this->base_id").attr('pre_width'));                    
                }
            });

JS;

        Yii::$app->view->registerJs($jquerySourceDefault);

    }

    public function set_dialog_style($style, $id_element=''){
        if($id_element==='' || $id_element = $this->base_id){
            Yii::$app->view->registerCss('#'.$this->base_id.'{'.$style.'}');
        }
    }

    public function on_show_hide($id_button){

        $this->selector_call = $id_button;

        $jsCode =<<<JS
        $(document).on("click", "#$id_button", function(){
            var this_display = $("#"+"$this->base_id"+"_draggable");
            if(this_display.is(':visible') === false){
                $("#$this->base_id").trigger("pre_show_"+"$this->base_id");
                this_display.show();
                $("#$this->base_id").trigger("past_show_"+"$this->base_id");
            }else{
                $("#$this->base_id").trigger("pre_hide_"+"$this->base_id");
                this_display.hide();
                $("#$this->base_id").trigger("past_hide_"+"$this->base_id");
            }
        });
JS;
        Yii::$app->view->registerJs($jsCode);
    }

    public function set_source($source){
        if($this->event_set === 0){
            $this->dialog_html = $this->dialog_html.$source;
        }else{
            $source = addslashes($source);
            $jsCode =<<<JS
        $("#"+"$this->base_id"+"_source_area").append("$source");
JS;
            Yii::$app->view->registerJs($jsCode);
        }
    }

    public function set_jquery_code($jquery){
        Yii::$app->view->registerJs($jquery);
    }

    public function set_template($name_template, $draggable = 1){
        if($this->event_set === 0) {
            $this_base_path = Yii::$app->basePath . '/views/dialogs/' . $name_template;
            $result_html = require_once($this_base_path . '/html_source.php');
            $result_jquery = require_once($this_base_path . '/jquery_source.php');
            $this->set_source($result_html);
            $this->set_dialog($draggable);
            $this->set_jquery_code($result_jquery);
        }
    }

}