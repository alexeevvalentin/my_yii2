<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\DBM;
use yii\helpers\Url;
use app\models\GenF;

/* @var $this yii\web\View */
/* @var $model app\models\Books */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="books-form">


    <?php


    //$column_arr = Yii::$app->db->createCommand('SHOW COLUMNS FROM '.$model::tableName())->queryAll();
    $primary_key = Yii::$app->db->createCommand('SHOW KEYS FROM '.$model::tableName().' WHERE Key_name = "PRIMARY"')->queryAll()[0]['Column_name'];

    $db_name = DBM::getDsnAttribute('dbname', Yii::$app->db->dsn);

    $files = \yii\helpers\FileHelper::findFiles(Yii::$app->basePath.'/views/dbm');

    $cur_file_name_for_filter = '_form_'.$model::tableName().'.php';

    //GenF::index_of('fsdf_form_books.php', $cur_file_name_for_filter);

    //print_r(GenF::index_of('fsdf_form_books.php', $cur_file_name_for_filter));

    $fl_isset_file = '';

    foreach($files as $k=>$v){
        if(GenF::index_of($v, $cur_file_name_for_filter)!==-1){
            $fl_isset_file = $v;
        }
    }

    ?>

    <?php $form = ActiveForm::begin(); ?>

    <?php
        if($fl_isset_file !== ''){
            echo $this->renderPhpFile($fl_isset_file, ['model'=>$model, 'form'=>$form, 'primary_key'=>$primary_key]);
        }else{
            foreach($model as $k=>$v){
                if($primary_key !== $k){
                    echo $form->field($model, $k)->textInput(['maxlength' => true]);
                }
            }
        }

    ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
