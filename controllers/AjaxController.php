<?php

namespace app\controllers;

use Yii;

class AjaxController extends \yii\web\Controller
{
    public function actionIndex()
    {
        return $this->render('index');
    }
    public function actionBooklist()
    {
        //$base_query = 'SELECT b.name FROM books b INNER JOIN book_author ba ON b.id = ba.id_book INNER JOIN book_shop bs ON b.id = bs.id_book WHERE ';

        $base_query = 'SELECT b.id, b.name, b.edition, b.year FROM books b ';

        $where_in_book = '';
        $where_in_author = '';
        $where_in_shop = '';
        $fl_isset = 0;
        if(isset($_REQUEST['data_json']['book'])){
            $where_in_book = 'b.id IN (';
            $fl_isset = 1;
            $fl_for = 0;
            foreach($_REQUEST['data_json']['book'] as $k=>$v){
                if($fl_for === 0){
                    $where_in_book = $where_in_book.$k;
                    $fl_for = 1;
                }else{
                    $where_in_book = $where_in_book.', '.$k;
                }
            }
            $where_in_book = $where_in_book.') ';
            //$base_query = 'SELECT b.name, ba.id_author, bs.id_shop FROM books b INNER JOIN book_author ba ON b.id = ba.id_book INNER JOIN book_shop bs ON b.id = bs.id_book WHERE ';
            //$query = Yii::$app->db->createCommand($base_query.'b.id = 2')->queryAll();
        }
        if(isset($_REQUEST['data_json']['author'])){
            $base_query = $base_query.'INNER JOIN book_author ba ON b.id = ba.id_book ';
            if($fl_isset === 1){
                $where_in_author = 'AND ';
            }else{
                $fl_isset = 1;
            }
            $where_in_author = $where_in_author.'ba.id_author IN (';
            $fl_for = 0;
            foreach($_REQUEST['data_json']['author'] as $k=>$v){
                if($fl_for === 0){
                    $where_in_author = $where_in_author.$k;
                    $fl_for = 1;
                }else{
                    $where_in_author = $where_in_author.', '.$k;
                }
            }
            $where_in_author = $where_in_author.') ';
        }
        if(isset($_REQUEST['data_json']['shop'])){
            $base_query = $base_query.'INNER JOIN book_shop bs ON b.id = bs.id_book ';
            if($fl_isset === 1){
                $where_in_shop = 'AND ';
            }else{
                $fl_isset = 1;
            }
            $where_in_shop = $where_in_shop.'bs.id_shop IN (';
            $fl_for = 0;
            foreach($_REQUEST['data_json']['shop'] as $k=>$v){
                if($fl_for === 0){
                    $where_in_shop = $where_in_shop.$k;
                    $fl_for = 1;
                }else{
                    $where_in_shop = $where_in_shop.', '.$k;
                }
            }
            $where_in_shop = $where_in_shop.') ';
        }
        if($fl_isset === 1){
            $base_query = $base_query.'WHERE ';
            $finquery = $base_query.$where_in_book.$where_in_author.$where_in_shop.' GROUP BY b.id';
            $query = Yii::$app->db->createCommand($finquery)->queryAll();

            $str_res = '';

            $q_a = Yii::$app->db->createCommand('SELECT * FROM authors')->queryAll();
            $q_a_filter = [];
            foreach($q_a as $k=>$v){
                $q_a_filter[$v['id']]['fio'] = $v['fio'];
            }
            $q_s = Yii::$app->db->createCommand('SELECT * FROM shops')->queryAll();
            $q_s_filter = [];
            foreach($q_s as $k=>$v){
                $q_s_filter[$v['id']]['name'] = $v['name'];
            }

            $q_e = Yii::$app->db->createCommand('SELECT * FROM editions')->queryAll();
            $q_e_filter = [];
            foreach($q_e as $k=>$v){
                $q_e_filter[$v['id']]['name'] = $v['name'];
            }

            foreach($query as $k=>$v){

                $str_res = $str_res.'<div style="display:table;">';

                $str_res = $str_res.'<div style="display:table-cell;padding:3px;">Название:&#160;<span style="font-weight:bold;">'.$v['name'].'</span></div>';

                $q_all_authors = Yii::$app->db->createCommand('SELECT id_author FROM book_author WHERE id_book = '.$v['id'])->queryAll();
                $str_authors = '';
                $fl_a = 0;
                foreach($q_all_authors as $k2=>$v2){
                    if($fl_a === 0){
                        $str_authors = $str_authors.$q_a_filter[$v2['id_author']]['fio'];

                        $fl_a = 1;
                    }else{
                        $str_authors = $str_authors.', '.$q_a_filter[$v2['id_author']]['fio'];

                    }
                }
                $str_res = $str_res.'<div style="display:table-cell;padding:3px;">Авторы:&#160;<span style="font-weight:bold;">'.$str_authors.'</span></div>';
                $str_res = $str_res.'<div style="display:table-cell;padding:3px;">Редакция:&#160;<span style="font-weight:bold;">'.$q_e_filter[$v['edition']]['name'].'</span></div>';

                $q_all_shops = Yii::$app->db->createCommand('SELECT id_shop FROM book_shop WHERE id_book = '.$v['id'])->queryAll();
                $str_shops = '';
                $fl_s = 0;
                foreach($q_all_shops as $k2=>$v2){
                    if($fl_s === 0){
                        $str_shops = $str_shops.$q_s_filter[$v2['id_shop']]['name'];
                        $fl_s = 1;
                    }else{
                        $str_shops = $str_shops.', '.$q_s_filter[$v2['id_shop']]['name'];

                    }
                }
                $str_res = $str_res.'<div style="display:table-cell;padding:3px;">Магазины:&#160;<span style="font-weight:bold;">'.$str_shops.'</span></div>';

                $str_res = $str_res.'<div style="display:table-cell;padding:3px;">Год издания:&#160;<span style="font-weight:bold;">'.$v['year'].'</span></div>';

                $str_res = $str_res.'</div>';

            }

            return $str_res;

        }else{
            return false;
        }

    }

}
