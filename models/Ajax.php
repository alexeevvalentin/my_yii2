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
class Ajax extends Model
{

    public function rules()
    {
        /*
        return [
            [['name', 'email'], 'required'],
            ['email', 'email'],
        ];
        */
    }

    public static function field($type_field, $this_name, $this_id, $url, $id_result='', $script_result='', $style='', $data_arr=[], $type_ajax='POST'){

        if($style === ''){
            if($type_field !== 'input'){
                $style = 'cursor:pointer;';
            }
        }else{
            if( (GenF::index_of($style, 'cursor') === -1) && $type_field !== 'input' ){
                $style = 'cursor:pointer;'.$style;
            }
        }

        if(mb_strtolower($type_field) === 'button'){
            echo '<input type="button" value="'.$this_name.'" id="'.$this_id.'" style="'.$style.'" />';
        }else if(mb_strtolower($type_field) === 'div'){
            echo '<div id="'.$this_id.'" style="'.$style.'" >'.$this_name.'</div>';
        }else if(mb_strtolower($type_field) === 'label'){
            echo '<label id="'.$this_id.'" style="'.$style.'" >'.$this_name.'</label>';
        }else if(mb_strtolower($type_field) === 'span'){
            echo '<span id="'.$this_id.'" style="'.$style.'" >'.$this_name.'</span>';
        }else if(mb_strtolower($type_field) === 'input'){
            echo '<input type="text" value="'.$this_name.'" id="'.$this_id.'" style="'.$style.'" />';
        }

        if(mb_strtolower($type_field) !== 'input'){
            Yii::$app->view->registerJs('
                var id_result = "' . $id_result . '";
                var script_result = "' . $script_result . '";
                var cur_color = $("#' . $this_id . '").css("color");
                $("#' . $this_id . '").click(function () {
                    $.ajax({
                        type: "' . $type_ajax . '",
                        url: "' . Url::to([$url]) . '",
                        data: {_csrf: "' . Yii::$app->request->getCsrfToken() . '", data_json: ' . json_encode($data_arr) . '},
                        beforeSend: function(){
                            $("#' . $this_id . '").prop("disabled", true);
                            $("#' . $this_id . '").css({"color":"grey", "cursor":"default"});
                        },
                        success: function(data){
                            $("#' . $this_id . '").prop("disabled", false);
                            $("#' . $this_id . '").css({"color":cur_color, "cursor":"pointer"});
                            if(id_result !== ""){
                                $("#"+id_result).html(data);
                                return true;
                            }
                            if(script_result !== ""){
                                eval(script_result);
                                return true;
                            }
                        }
                    });
                });
            ');
        }else{
            Yii::$app->view->registerJs('
                var id_result = "'.$id_result.'";
                var script_result = "'.$script_result.'";
                $("#'.$this_id.'").change(function () {
                    var this_value = $(this).val();
                    $.ajax({
                        type: "'.$type_ajax.'",
                        url: "'.Url::to([$url]).'",
                        data: {_csrf: "'.Yii::$app->request->getCsrfToken().'", data_json: '. json_encode($data_arr) .', input_val: $(this).val()},
                        beforeSend: function(){
                            $("#'.$this_id.'").prop("disabled", true);
                        },
                        success: function(data){
                            $("#'.$this_id.'").prop("disabled", false);                        
                            if(id_result !== ""){
                                $("#"+id_result).html(data);
                                return true;
                            }
                            if(script_result !== ""){
                                eval(script_result);
                                return true;
                            }
                        }
                    });
                    
                });
            ');
        }

    }


    public static function form($html_form_source, $this_id, $url, $id_result='', $script_result='', $type_ajax='POST', $enctype="multipart/form-data"){

        echo '<form id="'.$this_id.'" action="'.$url.'" method="'.$type_ajax.'" enctype="'.$enctype.'">';
        echo $html_form_source;
        echo '</form>';

        $url_to = Url::to([$url]);
        $csrf = Yii::$app->request->getCsrfToken();
        $script_result = addcslashes($script_result, '"');

        $jsCode = <<<JS

        $("#$this_id").submit(function(e) {
            //отмена действия по умолчанию для кнопки submit
            e.preventDefault();

            var form_data = [];
            form_data['files'] = [];
            form_data['input'] = [];

            var fd = new FormData();

            $(this).find('input[type="file"]').each(function(i, el){
                var file = $(el).prop('files')[0];
                fd.append('file_'+i, file);
            });

            $("#$this_id"+" :input").each(function(i, el){
                fd.append($(el).attr('id'), $(el).val());
            });
            
            $.ajax({
                type: "$type_ajax",
                url: "$url_to",
                cache: false,
                contentType: false,
                processData: false,
                data: fd,
                beforeSend: function(){
                    $("#$this_id").prop("disabled", true);
                },
                success: function(data){
                    $("#$this_id").prop("disabled", false);
                    if("$id_result" !== ""){
                        $("#$id_result").html(data);
                        return true;
                    }
                    if("$script_result" !== ""){
                        eval("$script_result");
                        return true;
                    }
                }
            });

            //отмена действия по умолчанию для кнопки submit
            e.preventDefault(); 
        });

JS;

        Yii::$app->view->registerJs($jsCode);

    }


}
