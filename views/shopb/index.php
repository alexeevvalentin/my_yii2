<?php
/* @var $this yii\web\View */

$this->title = 'Книжный магазин';
Yii::$app->name = 'Книги';

use yii\helpers\Html;

?>
<h1>Киоск-стенд</h1>

<p>Редактор данных:</p>
<div>
    <?=Html::a('Редактировать "КНИГУ"', ['dbm/index', 'table'=>'books']);?>
</div>
<div>
    <?=Html::a('Редактировать "АВТОРА"', ['dbm/index', 'table'=>'authors']);?>
</div>
<div>
    <?=Html::a('Редактировать "ИЗДАТЕЛЬСТВО"', ['dbm/index', 'table'=>'editions']);?>
</div>
<div>
    <?=Html::a('Редактировать "МАГАЗИН"', ['dbm/index', 'table'=>'shops']);?>
</div>
