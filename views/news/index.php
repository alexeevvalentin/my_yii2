<?php
/* @var $this yii\web\View */


use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use \app\models\News;
use \app\models\GenF;
use \app\models\Ajax;
use yii\widgets\LinkPager;

$this->title = 'Новости';
Yii::$app->name = 'Новости';

?>

<?php




if(!isset($_REQUEST['type']) && isset($_GET['type'])){$_REQUEST['type'] = $_GET['type'];}
if(!isset($_REQUEST['page']) && isset($_GET['page'])){$_REQUEST['page'] = $_GET['page'];}

print_r(Yii::$app->controller->action->id);


if(isset($_REQUEST['type'])){
    //$file_content = News::get_source_cache('http://api.innogest.ru/api/v33/news'.$_REQUEST['type'].'/', 'news_cur_rubric', 60);
    $file_content = News::get_source_cache('http://api.innogest.ru/api/v33/news', 'news_mem', 60);
    $pages = News::get_pages($file_content);
}else{
    $file_content = News::get_source_cache('http://api.innogest.ru/api/v33/news', 'news_mem', 60);
    $pages = News::get_pages($file_content);
}

$rubric = News::get_source_cache('http://api.innogest.ru/api/v33/news/rubrics', 'news_rub', 60);

if(!isset($_SESSION['limit'])){
    $_SESSION['limit'] = 15;
}else{
    if(isset($_REQUEST['limit']) && (int)$_REQUEST['limit'] !== 0){
        $_SESSION['limit'] = $_REQUEST['limit'];
    }
}

print_r($_SESSION['limit']);

// display pagination
$pages->pageSize=$_SESSION['limit'];
//print_r($pages);

//print_r($pages);

echo '<div style="background-color: #1e7e34;padding:8px;">';
    echo LinkPager::widget(array(
    'pagination' => $pages,
    ));
echo '</div>';





echo '<div class="div_tab">';

echo '<div style="width:200px;">';
foreach($rubric['content'] as $k=>$v){
    echo Html::a($v['name'], ['news/rubric', 'type'=>$v['type']]);
    echo '<br/>';
}
echo '</div>';
echo '<div style="width:800px;">';
foreach($file_content['content'] as $k=>$v){


    if( ((!isset($_REQUEST['type'])) || ((isset($_REQUEST['type'])) && ($_REQUEST['type']==='news'))) && (!isset($_REQUEST['nid'])) ){
        echo Html::a($v['title'], ['news/nid', 'nid'=>$v['nid']], ['style'=>['font-size'=>'16px', 'font-weight'=>'bold']]);
        echo '<div style="padding:8px; width:750px; height:150px;overflow:hidden;overflow-y:scroll;">'.$v['body'].'</div>';
        echo '<hr/>';
    }else if( (isset($_REQUEST['type'])) && (isset($_REQUEST['type']) && in_array($_REQUEST['type'], explode(',',$v['type']))) ){
        echo Html::a($v['title'], ['news/nid', 'nid'=>$v['nid']], ['style'=>['font-size'=>'16px', 'font-weight'=>'bold']]);
        echo '<div style="padding:8px; width:750px; height:150px;overflow:hidden;overflow-y:scroll;">'.$v['body'].'</div>';
        echo '<hr/>';
    }else if( (!isset($_REQUEST['type'])) && (isset($_REQUEST['nid']) && $_REQUEST['nid'] === $v['nid'] ) ){
        echo '<div style="font-size:16px;font-weight:bold;">'.$v['title'].'</div>';
        echo '<div class="div_person">'.$v['body'].'</div>';
    }

}
echo '</div>';
echo '<div style="width:200px;">';


//echo Yii::$app->request->url;

Ajax::field(
    'input',
    (int)$_SESSION['limit'],
    'id_limit',
    'news/changelimit','',
    'window.location.href = \"'. Yii::$app->request->url .'\";',''
);

//window.location.href = \"'.Url::to(['/news', $_REQUEST] ).'\";

echo '</div>';
echo '</div>';



/*
$file_content = @file_get_contents('http://api.innogest.ru/api/v33/news');
$file_content = '{"content":'.$file_content.'}';
$file_object = json_decode($file_content);
print_r( count($file_object->{'content'}) );
*/

?>