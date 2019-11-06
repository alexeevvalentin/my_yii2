<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Books */

$table_name = $model::tableName();

$this->title = 'Update : ' . $table_name;
$this->params['breadcrumbs'][] = ['label' => $table_name, 'url' => ['index', 'table'=>$table_name]];
$this->params['breadcrumbs'][] = ['label' => 'view', 'url' => ['view', 'table'=>$table_name, 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>

<div class="books-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
