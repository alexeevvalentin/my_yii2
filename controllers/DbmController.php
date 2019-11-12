<?php

namespace app\controllers;

use app\models\DbmSearch;
use app\models\GenF;
use Yii;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use app\models\DBM;
use yii\helpers\Json;

class DbmController extends \yii\web\Controller
{

    public function actionIndex($table)
    {
        $searchModel = new DbmSearch($table);
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
    public function actionView($table, $id)
    {
        return $this->render('view', [
            'model' => $this->findModel($table, $id),
        ]);
    }

    public function actionCreate($table)
    {
        $fl_isset_file = self::file_filter($table);
        if($fl_isset_file !== ''){
            $action = 'create';
            return require_once($fl_isset_file);
        }else {
            $model = $this->createModel($table);

            $pdata = Yii::$app->request->post();
            self::data_model_to_json($pdata);
            //self::data_model_htmlspecialchars($pdata);

            if ($model->load($pdata) && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id, 'table' => $table]);
            }

            return $this->render('create', [
                'model' => $model,
                'table' => $table
            ]);
        }
    }

    public function actionUpdate($table, $id)
    {

        $fl_isset_file = self::file_filter($table);

        //$fl_isset_file = '';

        if($fl_isset_file !== ''){
            $action = 'update';
            return require_once($fl_isset_file);
        }else{
            $model = $this->findModel($table, $id);

            $pdata = Yii::$app->request->post();
            self::data_model_to_json($pdata);
            //self::data_model_htmlspecialchars($pdata);

            if ($model->load($pdata) && $model->save()) {
                return $this->redirect(['update', 'id' => $model->id, 'table' => $table]);
            }

            return $this->render('update', [
                'model' => $model, 'table' => $table
            ]);
        }
    }

    public function actionDelete($table, $id)
    {

        $fl_isset_file = self::file_filter($table);
        if($fl_isset_file !== ''){
            $action = 'delete';
            return require_once($fl_isset_file);
        }else{
            $this->findModel($table, $id)->delete();
            return $this->redirect(['index', 'table' => $table]);
        }

    }

    protected function findModel($table, $id)
    {

        $dbm_model = new DBM();
        $dbm_model::setTableName($table);

        if (($model = $dbm_model::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
    protected function createModel($table)
    {
        $dbm_model = new DBM();
        $dbm_model::setTableName($table);
        return $dbm_model;
    }

    private static function data_model_to_json(&$post_data){
        if(isset($post_data['DBM'])) {
            foreach($post_data['DBM'] as $k => $v) {
                if(is_array($v)){
                    $post_data['DBM'][$k] = Json::encode($v);
                }
            }
        }
    }

    private static function data_model_htmlspecialchars(&$post_data){
        if(isset($post_data['DBM'])) {
            foreach($post_data['DBM'] as $k => $v) {
                if(is_string($v)){
                    $post_data['DBM'][$k] = htmlspecialchars($v);
                }
            }
        }
    }

    private static function file_filter($table){
        $files = \yii\helpers\FileHelper::findFiles(Yii::$app->basePath.'\views\dbm');
        $cur_file_name_for_filter = '_controller_'.$table.'.php';
        $fl_isset_file = '';
        foreach($files as $k=>$v){
            if(GenF::index_of($v, $cur_file_name_for_filter)!==-1){
                $fl_isset_file = $v;
            }
        }
        return $fl_isset_file;
    }

}
