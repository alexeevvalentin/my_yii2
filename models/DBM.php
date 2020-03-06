<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\helpers\Html;
use yii\helpers\Url;


class DBM extends \yii\db\ActiveRecord
{
    protected static $smodel_name;

    public static function tableName()
    {
        return self::$smodel_name;
    }
    public static function setTableName($table)
    {
        self::$smodel_name = $table;
    }

    public function rules()
    {

        return [[$this->getcolumns_nopk(), 'default']];

        /*
        return [
            [['name', 'author', 'edition', 'year'], 'required'],
            [['name', 'author', 'edition', 'year'], 'string', 'max' => 255],
        ];
        */
    }

    public function attributeLabels()
    {
        return $this->getLabels();
    }

    public function getLabels(){
        $arrLabel = [];
        foreach($this as $k=>$v){
            $arrLabel[$k] = strtoupper($k);
        }
        return $arrLabel;
    }

    public function getcolumns(){
        $arr = [];
        foreach($this as $k=>$v){
            array_push($arr, $k);
        }
        return $arr;
    }

    public function getcolumns_nopk(){
        $arr = [];
        $primary_key = Yii::$app->db->createCommand('SHOW KEYS FROM '.self::$smodel_name.' WHERE Key_name = "PRIMARY"')->queryAll()[0]['Column_name'];
        foreach($this as $k=>$v){
            if($primary_key !== $k){
                array_push($arr, $k);
            }
        }
        return $arr;
    }

    public static function getDsnAttribute($name, $dsn)
    {
        if (preg_match('/' . $name . '=([^;]*)/', $dsn, $match)) {
            return $match[1];
        } else {
            return null;
        }
    }

}
