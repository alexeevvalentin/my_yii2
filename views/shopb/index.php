<?php
/* @var $this yii\web\View */

$this->title = 'Книжный магазин';
Yii::$app->name = 'Книги';

use app\models\tag;
use yii\helpers\Html;
use app\models\DBM;
use app\models\Books;
use yii\helpers\Json;
use yii\helpers\Url;


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

<div style="padding:16px;"></div>

<p>Содержимое:</p>

<?php

$model_b = new DBM();
$model_b::setTableName('books');
$arr_b = $model_b::find()->all();
$a_b[''] = '';
foreach($arr_b as $k=>$v){
    $a_b[$v->id] = $v->name;
}

$model_a = new DBM();
$model_a::setTableName('authors');
$arr_a = $model_a::find()->all();
$a_a[''] = '';
foreach($arr_a as $k=>$v){
    $a_a[$v->id] = $v->fio;
}

$model_s = new DBM();
$model_s::setTableName('shops');
$arr_s = $model_s::find()->all();
$a_s[''] = '';
foreach($arr_s as $k=>$v){
    $a_s[$v->id] = $v->name;
}


?>

<div style="display:table;">

    <div style="display:table-cell;width:250px;padding:8px;">
        <div>Книга:</div>
        <div style="padding-bottom:3px;">
            <?php tag::ecocombo($model_b, 'book', $a_b, ['placeholder'=>'Выберите книгу']); ?>
        </div>
        <div>
            <?php tag::select_monitor($model_b, 'book', 'book_monitor', $a_b); ?>
        </div>
    </div>

    <div style="display:table-cell;width:250px;padding:8px;">
        <div>Автор:</div>
        <div style="padding-bottom:3px;">
            <?php tag::ecocombo($model_a, 'author', $a_a, ['placeholder'=>'Выберите автора']); ?>
        </div>
        <div>
            <?php tag::select_monitor($model_a, 'author', 'author_monitor', $a_a); ?>
        </div>
    </div>

    <div style="display:table-cell;width:250px;padding:8px;">
        <div>Магазин:</div>
        <div style="padding-bottom:3px;">
            <?php tag::ecocombo($model_s, 'shop', $a_s, ['placeholder'=>'Выберите магазин']); ?>
        </div>
        <div>
            <?php tag::select_monitor($model_s, 'shop', 'shop_monitor', $a_s); ?>
        </div>
    </div>

</div>

    <div id="result_data" style="background-color:#FFB686;border: 3px solid #FF924B;border-radius:8px;display:none;">

    </div>

<?php

//ecocombo_btn

$CSRF = Yii::$app->request->getCsrfToken();
$baseURL = Yii::$app->request->baseUrl;

$jsIndex = <<<JS

$(document).on('click', '[class$="_option_row"]',function(){
    var book_a =  JSON.parse($('#book_monitor').attr("value"));
    var author_a =  JSON.parse($('#author_monitor').attr("value"));
    var shop_a =  JSON.parse($('#shop_monitor').attr("value"));

    var CSRF = "$CSRF";
    
    var jsondata = {};

    if(Object.keys(book_a).length !== 0){jsondata['book'] = book_a;}
    if(Object.keys(author_a).length !== 0){jsondata['author'] = author_a;}
    if(Object.keys(shop_a).length !== 0){jsondata['shop'] = shop_a;}

    $.ajax({
        type: "POST",
        url: "$baseURL/ajax/booklist",
        data: {_csrf: CSRF, data_json: jsondata},
        success: function(data){
            if(data != ''){
                $('#result_data').css('display','block');
            }else{
                $('#result_data').css('display','none');
            }
            $('#result_data').html(data);
        }
    });
});

$(document).on('click', '[class$="_monitor"][class^="del_"]',function(){
    var book_a =  JSON.parse($('#book_monitor').attr("value"));
    var author_a =  JSON.parse($('#author_monitor').attr("value"));
    var shop_a =  JSON.parse($('#shop_monitor').attr("value"));

    var CSRF = "$CSRF";
    
    var jsondata = {};

    if(Object.keys(book_a).length !== 0){jsondata['book'] = book_a;}
    if(Object.keys(author_a).length !== 0){jsondata['author'] = author_a;}
    if(Object.keys(shop_a).length !== 0){jsondata['shop'] = shop_a;}

    $.ajax({
        type: "POST",
        url: "$baseURL/ajax/booklist",
        data: {_csrf: CSRF, data_json: jsondata},
        success: function(data){
            if(data != ''){
                $('#result_data').css('display','block');
            }else{
                $('#result_data').css('display','none');
            }
            $('#result_data').html(data);
        }
    });
});


JS;


Yii::$app->view->registerJs($jsIndex);


?>