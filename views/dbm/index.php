<?php
/* @var $this yii\web\View */

use yii\grid\GridView;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

use app\models\DBM;

if(!isset($_REQUEST['table'])){
    $_REQUEST['table'] = $_GET['table'];
}

?>

<?php

$dbmModel = new DBM();
$dbmModel::setTableName($searchModel::tableName());

$obj_keys = array_keys($dbmModel->attributes);
$PK = $dbmModel->tableSchema->primaryKey[0];

$arr_columns = 'use yii\helpers\Html;use yii\helpers\Url; '.
'$arrcol = [["class" => "yii\grid\SerialColumn"], ';

foreach($obj_keys as $k=>$v){
    $arr_columns = $arr_columns.'"'.$v.'", ';
}
$arr_columns = $arr_columns.'["class" => "yii\grid\ActionColumn"';
//---------------- ДОБАВЛЕНИЕ ИМЕНИ ТАБЛИЦЫ В ДЕЙСТВИЯ, Для аналитической работы функционала Dbm
$arr_columns = $arr_columns.', "buttons" => ["view" => function ($url, $model, $key) {return Html::a("<span class=\"glyphicon glyphicon-eye-open\"></span>", Url::to(["dbm/view", "id" => $model->id, "table"=>$_REQUEST["table"]]));}, ';
$arr_columns = $arr_columns.'"update" => function ($url, $model, $key) {return Html::a("<span class=\"glyphicon glyphicon-pencil\"></span>", Url::to(["dbm/update", "id" => $model->id, "table"=>$_REQUEST["table"]]));}, ';
$arr_columns = $arr_columns.'"delete" => function ($url, $model, $key) {return Html::a("<span class=\"glyphicon glyphicon-trash\"></span>", Url::to(["dbm/delete", "id" => $model->id, "table"=>$_REQUEST["table"]]));} ] ';
//---------------- ДОБАВЛЕНИЕ ИМЕНИ ТАБЛИЦЫ В ДЕЙСТВИЯ, Для аналитической работы функционала Dbm
$arr_columns = $arr_columns.']];';

eval($arr_columns);

?>

<p>
    <?= Html::a('Create '.$searchModel::tableName(), ['create', 'table'=>$_REQUEST['table']], ['class' => 'btn btn-success']) ?>
</p>


<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'filterModel' => $searchModel,
    'columns' => $arrcol
        /*
        [
            ['class' => 'yii\grid\SerialColumn'],

            'id',
            //'name',
            //'author',
            //'edition',
            //'year',

            ['class' => 'yii\grid\ActionColumn'],
        ]
        */
    ,
]);  ?>
