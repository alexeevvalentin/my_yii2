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

// ДЛЯ КРАСИВОГО URL
if(!isset($_REQUEST['type']) && isset($_GET['type'])){$_REQUEST['type'] = $_GET['type'];}
if(!isset($_REQUEST['page']) && isset($_GET['page'])){$_REQUEST['page'] = $_GET['page'];}
if(!isset($_REQUEST['nid']) && isset($_GET['nid'])){$_REQUEST['nid'] = $_GET['nid'];}
// ДЛЯ КРАСИВОГО URL

if($_REQUEST['page']==''){$_REQUEST['page'] = 1;}

if(isset($_REQUEST['nid'])){
    $file_content = News::get_source_cache('http://api.innogest.ru/api/v33/news/node?nid='.$_REQUEST['nid'], 'news_nid_'.$_REQUEST['nid'], 60);
    $pages = News::get_pages($file_content);
}else if(isset($_REQUEST['type'])){
    $file_content = News::get_source_cache('http://api.innogest.ru/api/v33/news/'.$_REQUEST['type'], 'news_rubric_'.$_REQUEST['type'], 60);
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

// display pagination
$pages->pageSize=$_SESSION['limit'];
//print_r($pages);

//print_r($pages);

echo '<div class="div_tab">';

echo '<div style="width:200px;">';
foreach($rubric['content'] as $k=>$v){
    echo Html::a($v['name'], ['news/rubric', 'type'=>$v['type']]);
    echo '<br/>';
}
echo '</div>';
echo '<div style="width:800px;">';

$start_page = (1*$_REQUEST['page'] - 1)*$_SESSION['limit'];
$end_page = $_REQUEST['page']*$_SESSION['limit'];
$schet = 0;

foreach($file_content['content'] as $k=>$v){
    if($schet >= $start_page && $schet < $end_page){
        if( ((!isset($_REQUEST['type'])) || ((isset($_REQUEST['type'])) && ($_REQUEST['type']==='news'))) && (!isset($_REQUEST['nid'])) ){
            echo Html::a($v['title'], ['news/nid', 'nid'=>$v['nid']], ['style'=>['font-size'=>'16px', 'font-weight'=>'bold']]);

            $data_str = (string)$v['create'];
            echo '<div style="padding-top:3px;padding-bottom:3px;">Дата создания:&#160;'.date('d.m.Y H:i:s', $data_str).'</div>';
            echo '<div>'.Html::img(Url::to($v['img_url']), ['alt' => 'logo', 'style'=>'width:130px;']).'</div>';

            echo '<div style="padding:8px; width:750px; height:150px;overflow:hidden;overflow-y:scroll;">'.$v['body'].'</div>';
            echo '<hr/>';
        }else if(isset($_REQUEST['type']) && in_array($_REQUEST['type'], explode(',',$v['type']))){
            echo Html::a($v['title'], ['news/nid', 'nid'=>$v['nid']], ['style'=>['font-size'=>'16px', 'font-weight'=>'bold']]);

            $data_str = (string)$v['create'];
            echo '<div style="padding-top:3px;padding-bottom:3px;">Дата создания:&#160;'.date('d.m.Y H:i:s', $data_str).'</div>';
            echo '<div>'.Html::img(Url::to($v['img_url']), ['alt' => 'logo', 'style'=>'width:130px;']).'</div>';

            echo '<div style="padding:8px; width:750px; height:150px;overflow:hidden;overflow-y:scroll;">'.$v['body'].'</div>';
            echo '<hr/>';
        }else if(isset($_REQUEST['nid']) && $_REQUEST['nid'] === $v['nid']){
            echo '<div style="font-size:16px;font-weight:bold;">'.$v['title'].'</div>';
            echo '<div class="div_person">'.$v['body'].'</div>';
        }
    }
    $schet = $schet + 1;
}
echo '</div>';
echo '<div style="width:200px;">';

echo 'Лимит новостей на странице: ';

Ajax::field(
    'input',
    (int)$_SESSION['limit'],
    'id_limit',
    'news/changelimit',
    '',
    'window.location.href = \"'. Yii::$app->request->url .'\";',
    'width:30px;'
);
echo '</div>';
echo '</div>';

echo '<div style="padding:8px;display:table-cell;text-align:center;position:fixed;top:80%;left:5%;">';
echo LinkPager::widget(array(
    'pagination' => $pages,
));
echo '</div>';

/*
$file_content = @file_get_contents('http://api.innogest.ru/api/v33/news');
$file_content = '{"content":'.$file_content.'}';
$file_object = json_decode($file_content);
print_r( count($file_object->{'content'}) );
*/

?>