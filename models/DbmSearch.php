<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;


/**
 * BooksSearch represents the model behind the search form of `app\models\Books`.
 */
class DbmSearch extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */

    private $model_name;
    private static $smodel_name;

    function __construct($table){
        $this->model_name = $table;
        self::$smodel_name = $table;
    }

    public static function tableName()
    {
        return self::$smodel_name;
    }

    public function rules()
    {

        $column_arr = Yii::$app->db->createCommand('SHOW COLUMNS FROM '.$this->model_name)->queryAll();
        $primary_key = Yii::$app->db->createCommand('SHOW KEYS FROM '.$this->model_name.' WHERE Key_name = "PRIMARY"')->queryAll()[0]['Column_name'];

        $eval_arr = '$arr = [';
        $eval_arr = $eval_arr.'[["'.$primary_key.'"], "integer"],[[';
        foreach($column_arr as $k=>$v){
            if($v['Key'] != 'PRI'){
                $eval_arr = $eval_arr.'"'.$v['Field'].'", ';
            }
        }
        $eval_arr = $eval_arr.'], "safe"]];';
        eval($eval_arr);
        return $arr;

    }

    /**
     * {@inheritdoc}
     */



    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }


    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */


    public function search($params)
    {

        $eval_query = '';
        $eval_query = $eval_query.'use app\models\DBM;';
        $eval_query = $eval_query.'use yii\data\ActiveDataProvider;';
        $eval_query = $eval_query.'$dbm_model = new DBM();';
        $eval_query = $eval_query.'$dbm_model::setTableName("'.$this->model_name.'");';
        $eval_query = $eval_query.'$query = $dbm_model::find();';
        $eval_query = $eval_query.'$dataProvider = new ActiveDataProvider(["query" => $query]);';
        $eval_query = $eval_query.'$this->load($params);';
        $eval_query = $eval_query.'if (!$this->validate()) {return $dataProvider;}';
        $eval_query = $eval_query.'$obj = $dbm_model;';
        $eval_query = $eval_query.'$obj_keys = array_keys($obj->attributes);';
        $eval_query = $eval_query.'$PK = $obj->tableSchema->primaryKey[0];';
        $eval_query = $eval_query.'foreach($obj_keys as $k=>$v){';
        $eval_query = $eval_query.'if($v == $PK){';
        $eval_query = $eval_query.'$query->andFilterWhere(["$v" => $this->$v]);';
        $eval_query = $eval_query.'}else{';
        $eval_query = $eval_query.'$query->andFilterWhere(["like", "$v", $this->$v]);';
        $eval_query = $eval_query.'}}';
        $eval_query = $eval_query.'return $dataProvider;';

        return eval($eval_query);


        /*
        $eval_query = '';
        $eval_query = $eval_query.'use app\models\\'.$this->model_name.';';
        $eval_query = $eval_query.'use yii\data\ActiveDataProvider;';
        $eval_query = $eval_query.'$query = '.$this->model_name.'::find();';
        $eval_query = $eval_query.'$dataProvider = new ActiveDataProvider(["query" => $query]);';
        $eval_query = $eval_query.'$this->load($params);';
        $eval_query = $eval_query.'if (!$this->validate()) {return $dataProvider;}';
        $eval_query = $eval_query.'$obj = new '.$this->model_name.'();';
        $eval_query = $eval_query.'$obj_keys = array_keys($obj->attributes);';
        $eval_query = $eval_query.'$PK = $obj->tableSchema->primaryKey[0];';
        $eval_query = $eval_query.'foreach($obj_keys as $k=>$v){';
        $eval_query = $eval_query.'if($v == $PK){';
        $eval_query = $eval_query.'$query->andFilterWhere(["$v" => $this->$v]);';
        $eval_query = $eval_query.'}else{';
        $eval_query = $eval_query.'$query->andFilterWhere(["like", "$v", $this->$v]);';
        $eval_query = $eval_query.'}}';
        $eval_query = $eval_query.'return $dataProvider;';

        return eval($eval_query);
        */
    }


}
