<?php

namespace app\controllers;

use yii\filters\VerbFilter;

class DbmController extends \yii\web\Controller
{

    public function actionIndex()
    {
        return $this->render('index');
    }
    public function actionTest()
    {
        return 'TEST';
    }

}
