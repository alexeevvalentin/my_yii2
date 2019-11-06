<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Books */

$table_name = $model::tableName();

$this->title = 'Create '.$table_name;
$this->params['breadcrumbs'][] = ['label' => $table_name, 'url' => ['index', 'table'=>$table_name]];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="books-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
