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

        $arr_shop = [];
        if(isset($pdata['DBM']['shop_monitor_hide'])){
            Yii::$app->db->createCommand('DELETE FROM book_shop WHERE id_book = '.$id)->execute();
            $arr_shop = Json::decode($pdata['DBM']['shop_monitor_hide']);
        }

        foreach($arr_shop as $k=>$v){
            $m_book_shop = $this->createModel('book_shop');
            $cur_data_b_s = [];
            $cur_data_b_s['DBM'] = ['id_book'=>$id, 'id_shop'=>$k];
            if ($m_book_shop->load($cur_data_b_s) && $m_book_shop->save()){}else{
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
    }else if($action === 'create'){

        $model = $this->createModel($table);
        $pdata = Yii::$app->request->post();
        self::data_model_to_json($pdata);

        if(isset($pdata['DBM']['edition'])){
            $dbm_edition = $pdata['DBM']['edition'];
            $pos_key_edit = GenF::index_of($dbm_edition,'-');
            if($pos_key_edit !== -1){
                $dbm_edition = substr($dbm_edition,0, GenF::index_of($dbm_edition,'-'));
                $pdata['DBM']['edition'] = trim($dbm_edition);
            }
        }

        if ($model->load($pdata) && $model->save()) {
            $arr_author = [];
            if(isset($pdata['DBM']['author_monitor_hide'])){
                Yii::$app->db->createCommand('DELETE FROM book_author WHERE id_book = '.$model->id)->execute();
                $arr_author = Json::decode($pdata['DBM']['author_monitor_hide']);
            }
            foreach($arr_author as $k=>$v){
                $m_book_author = $this->createModel('book_author');
                $cur_data_b_a = [];
                $cur_data_b_a['DBM'] = ['id_book'=>$model->id, 'id_author'=>$k];
                if ($m_book_author->load($cur_data_b_a) && $m_book_author->save()){}else{
                    //echo 'Невозможно осуществить запись в таблицу "book_author"';
                    //die;
                }
            }

            $arr_shop = [];
            if(isset($pdata['DBM']['shop_monitor_hide'])){
                Yii::$app->db->createCommand('DELETE FROM book_shop WHERE id_book = '.$model->id)->execute();
                $arr_shop = Json::decode($pdata['DBM']['shop_monitor_hide']);
            }

            foreach($arr_shop as $k=>$v){
                $m_book_shop = $this->createModel('book_shop');
                $cur_data_b_s = [];
                $cur_data_b_s['DBM'] = ['id_book'=>$model->id, 'id_shop'=>$k];
                if ($m_book_shop->load($cur_data_b_s) && $m_book_shop->save()){}else{
                    //echo 'Невозможно осуществить запись в таблицу "book_author"';
                    //die;
                }
            }

            return $this->redirect(['view', 'id' => $model->id, 'table' => $table]);
        }


        return $this->render('create', [
            'model' => $model,
            'table' => $table
        ]);

    }else if($action === 'delete'){

        Yii::$app->db->createCommand('DELETE FROM book_author WHERE id_book = '.$id)->execute();
        Yii::$app->db->createCommand('DELETE FROM book_shop WHERE id_book = '.$id)->execute();
        $this->findModel($table, $id)->delete();
        return $this->redirect(['index', 'table' => $table]);

    }
}

?>