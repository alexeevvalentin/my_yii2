<?php

namespace app\models;


use Yii;
use yii\base\Model;
use app\models\GenF;
use yii\helpers\Url;

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
                var id_result = "'.$id_result.'";
                var script_result = "'.$script_result.'";
                $("#'.$this_id.'").click(function () {
                    $.ajax({
                        type: "'.$type_ajax.'",
                        url: "'.Url::to([$url]).'",
                        data: {_csrf: "'.Yii::$app->request->getCsrfToken().'", data_json: '. json_encode($data_arr) .'},
                        success: function(data){
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
                        success: function(data){
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

}
