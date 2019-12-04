<?php

$this->title = 'Используемые тэги';
Yii::$app->name = 'Тэги';

use app\models\GenF;
use app\models\tag;
use yii\helpers\Html;
use app\models\DBM;
use yii\helpers\Json;
use yii\helpers\Url;



$file_json = GenF::get_file_source(Yii::$app->basePath.'\views\taglist\kladr_json.txt');
$f_j_arr = Json::decode($file_json);

$file_json_full = GenF::get_file_source(Yii::$app->basePath.'\views\taglist\kladrfull.txt');
$f_j_arr_full = $file_json_full;
//$f_j_arr_full = Json::decode($file_json_full);

?>

<div>
    <div style="font-weight:bold;">ecocombo</div>
    <div style="font-style:italic;">Экономичное combo (select), на 10000 строк данных</div>
</div>
<div style="padding-top:3px;padding-bottom:12px;">
    <?php tag::ecocombo('model', 'kladr10000', $f_j_arr, ['placeholder'=>'Выберите объект кладера', 'style'=>'width:300px;']); ?>
</div>

<div>
    <div style="font-weight:bold;">ecocombo</div>
    <div style="font-style:italic;">Экономичное combo (select), на 228818 строк данных (полный набор данных KLADR.DBF)(Поиск регистронезависимый)</div>
</div>
<div style="padding-top:3px;padding-bottom:12px;">
    <?php tag::ecocombo('model', 'kladr_i', $f_j_arr_full, ['placeholder'=>'Выберите объект кладера', 'style'=>'width:300px;']); ?>
</div>

<div>
    <div style="font-weight:bold;">ecocombo</div>
    <div style="font-style:italic;">Экономичное combo (select), на 228818 строк данных (полный набор данных KLADR.DBF)(Поиск регистрозависимый)</div>
</div>
<div style="padding-top:3px;padding-bottom:12px;">
    <?php tag::ecocombo('model', 'kladr', $f_j_arr_full, ['placeholder'=>'Выберите объект кладера', 'style'=>'width:300px;'], null, "", 100, 8); ?>
</div>

<div>
    <div style="font-weight:bold;">select_monitor</div>
    <div style="font-style:italic;">Монитор выбора, идет "в связке" с ecocombo, необходим для множественное выбора из ecocombo (Связано с регистронезависимым поисковиком где name="kladr_i")</div>
</div>
<div style="padding-top:3px;padding-bottom:12px;">
    <?php tag::select_monitor('model', 'kladr_i', 'monitor_kladr_i'); ?>
</div>

<div>
    <div style="font-weight:bold;">monetary_field</div>
    <div style="font-style:italic;">Double формат, разделитель "."</div>
</div>
<div style="padding-top:3px;padding-bottom:12px;">
    <?php tag::monetary_field('model', 'monetary', 12,3); ?>
</div>



