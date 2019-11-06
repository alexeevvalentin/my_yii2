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

$model_b_a = new DBM();
$model_b_a::setTableName('book_author');
$query_authors = $model_b_a::find()->where(['id_book' => $model->id])->all();

$arr_load_authors = [];

foreach($query_authors as $k=>$v){
    //array_push($arr_load_authors, $v->id_author);
    $arr_load_authors[$v->id_author] = $a_a[$v->id_author];
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


print_r($model->attributes);

//echo $form->field($model, 'year')->textInput(['maxlength' => true])->label('YEAR');



//Json::encode([0=>'one', 1=>'two', 2=>'three', 3=>'four'])


//echo '<input type="text" id="dbm-name" class="form-control" name="DBM[namer]" />';


//$form->field($model, 'author')
    //->on('change', $model->author = Json::encode($model->author)) ;
    //->on('change', $model->author = Json::encode($model->author));

//$model->beforeSave()

/*
foreach($model as $k=>$v){
    if($primary_key !== $k){
        echo $form->field($model, $k)->textInput(['maxlength' => true]);
    }
}
*/

?>