<?php

namespace app\controllers;

use app\models\regression;
use app\models\GenF;
use app\models\Ajax;
use yii\helpers\Json;

class RegressionController extends \yii\web\Controller
{

    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionSettingreg()
    {
        $in_base_id = $_REQUEST['base_id'];
        $in_data_json = JSON::decode($_REQUEST['data_json']);

        return regression::draw_setting_reg($in_base_id, $in_data_json);
    }

    public function actionAjaxreportregression(){

        $in_data_json = Json::decode($_REQUEST['data_json']);
        $in_base_id = $_REQUEST['base_id'];
        $in_checked_x = $_REQUEST['checked_x'];
        $in_checked_y = $_REQUEST['checked_y'];
        $in_a0 = 1*$_REQUEST['a0'];
        if(isset($_REQUEST['x_forecast'])){
            $x_forecast = $_REQUEST['x_forecast'];
        }else{
            $x_forecast = [];
        }

        return regression::draw_result_reg($in_data_json, $in_base_id, $in_checked_x, $in_checked_y, $in_a0, $x_forecast);
    }

}
