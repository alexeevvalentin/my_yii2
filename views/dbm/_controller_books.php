<?php

use app\models\DBM;
use app\models\GenF;
use yii\helpers\Json;

$db_name = DBM::getDsnAttribute('dbname', Yii::$app->db->dsn);

if($db_name === 'minweb'){
    if($action === 'update') {

        $pdata = Yii::$app->request->post();

        if(isset($pdata['DBM']['edition'])){
            $dbm_edition = $pdata['DBM']['edition'];
            $pos_key_edit = GenF::index_of($dbm_edition,'-');
            if($pos_key_edit !== -1){
                $dbm_edition = substr($dbm_edition,0, GenF::index_of($dbm_edition,'-'));
                $pdata['DBM']['edition'] = trim($dbm_edition);
            }
        }

        $arr_author = [];
        if(isset($pdata['DBM']['author_monitor_hide'])){
            Yii::$app->db->createCommand('DELETE FROM book_author WHERE id_book = '.$id)->execute();
            $arr_author = Json::decode($pdata['DBM']['author_monitor_hide']);
        }
        self::data_model_to_json($pdata);

        foreach($arr_author as $k=>$v){
            $m_book_author = $this->createModel('book_author');
            $cur_data_b_a = [];
            $cur_data_b_a['DBM'] = ['id_book'=>$id, 'id_author'=>$k];
            if ($m_book_author->load($cur_data_b_a) && $m_book_author->save()){}else{
                //echo 'Невозможно осуществить запись в таблицу "book_author"';
                //die;
            }
        }

        $m_books = $this->findModel('books', $id);
        if ($m_books->load($pdata) && $m_books->save()) {
            return $this->redirect(['update', 'id' => $m_books->id, 'table' => 'books']);
        }

        return $this->render('update', [
            'model' => $m_books, 'table' => 'books'
        ]);


    }
}

?>