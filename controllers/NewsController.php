<?php

namespace app\controllers;

use Yii;
use app\models\News;
use yii\data\Pagination;
use app\models\Books;
use yii\helpers\Url;

class NewsController extends \yii\web\Controller
{

    public function actionIndex()
    {
        return $this->render('index');
    }
    public function actionRubric($type)
    {
        return $this->render('index', ['type'=>$type]);
    }
    public function actionNid($nid)
    {
        return $this->render('index', ['nid'=>$nid]);
    }
    public function actionChangelimit()
    {



        $_SESSION['limit'] = (int)$_REQUEST['input_val'];

        return $_SESSION['limit'];

        //$options = $_REQUEST['data_json'];
        //$options['limit'] = (int)$_REQUEST['input_val'];
        //return $this->render('index', $options);
    }

}
