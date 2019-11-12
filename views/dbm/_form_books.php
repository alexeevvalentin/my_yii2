<?php

use app\models\DBM;
use yii\helpers\Json;
use app\models\tag;

echo $form->field($model, 'name')->textInput(['maxlength' => true]);

//-------------- AUTHOR -----------------------------
$author_model = new DBM();
$author_model::setTableName('authors');
$arr_authors = $author_model::find()->all();
$a_a[''] = 'Выберите автора';
foreach($arr_authors as $k=>$v){
    $a_a[$v->id] = $v->fio;
}
//-------------- AUTHOR -----------------------------

//-------------- EDITION ----------------------------
$edition_model = new DBM();
$edition_model::setTableName('editions');
$arr_editions = $edition_model::find()->all();
$a_e[''] = 'Выберите издателя';
foreach($arr_editions as $k=>$v){
    $a_e[$v->id] = $v->name;
}
//-------------- EDITION ----------------------------

//-------------- SHOP -------------------------------
$shop_model = new DBM();
$shop_model::setTableName('shops');
$arr_shops = $shop_model::find()->all();
$a_s[''] = 'Выберите магазин';
foreach($arr_shops as $k=>$v){
    $a_s[$v->id] = $v->name;
}
//-------------- SHOP -------------------------------

$model_b_a = new DBM();
$model_b_a::setTableName('book_author');
$query_authors = $model_b_a::find()->where(['id_book' => $model->id])->all();
$arr_load_authors = [];
foreach($query_authors as $k=>$v){
    //array_push($arr_load_authors, $v->id_author);
    $arr_load_authors[$v->id_author] = $a_a[$v->id_author];
}

$model_b_s = new DBM();
$model_b_s::setTableName('book_shop');
$query_shops = $model_b_s::find()->where(['id_book' => $model->id])->all();
$arr_load_shops = [];
foreach($query_shops as $k=>$v){
    //array_push($arr_load_authors, $v->id_author);
    $arr_load_shops[$v->id_shop] = $a_s[$v->id_shop];
}

//echo $form->field($model, 'b_a_author')->listBox($a_a, ['value' => $arr_load_authors, 'multiple' => true])->label('AUTHOR');

echo '<div class="form-group field-dbm-author">';
echo '<label class="control-label" for="dbm-author">AUTHOR</label>';
echo '<div>';
echo '<div style="padding-bottom:3px;">';
tag::ecocombo($model, 'author', $a_a, ['placeholder'=>'Выберите автора']);
echo '</div>';
tag::select_monitor($model, 'author', 'author_monitor', '', $arr_load_authors);
echo '</div>';
echo '<div class="help-block"></div>';
echo '</div>';

echo '<div class="form-group field-dbm-author">';
echo '<label class="control-label" for="dbm-author">EDITION</label>';
echo '<div>';
if(isset($model->edition)){
    tag::ecocombo($model, 'edition', $a_e, ['placeholder'=>'Выберите издателя'], $model->edition.' - '.$a_e[$model->edition]);
}else{
    tag::ecocombo($model, 'edition', $a_e, ['placeholder'=>'Выберите издателя']);
}
echo '</div>';
echo '<div class="help-block"></div>';
echo '</div>';

echo '<div class="form-group field-dbm-author">';
echo '<label class="control-label" for="dbm-author">YEAR</label>';
echo '<div>';
    tag::input($model, 'year');
echo '</div>';
echo '<div class="help-block"></div>';
echo '</div>';

echo '<div class="form-group field-dbm-author">';
echo '<label class="control-label" for="dbm-author">SHOP</label>';
echo '<div>';
echo '<div style="padding-bottom:3px;">';
tag::ecocombo($model, 'shop', $a_s, ['placeholder'=>'Выберите магазин']);
echo '</div>';
tag::select_monitor($model, 'shop', 'shop_monitor', '', $arr_load_shops);
echo '</div>';
echo '<div class="help-block"></div>';
echo '</div>';


?>