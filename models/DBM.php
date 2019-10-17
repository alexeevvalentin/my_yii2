<?php

namespace app\models;

use Yii;
use yii\base\Model;
/**
 * This is the model class for table "books".
 *
 * @property int $id
 * @property string $name
 * @property string $author
 * @property string $edition
 * @property string $year
 */
class DBM extends Model
{
	public $name;
    public $email;
 
    public function rules()
    {
        return [
            [['name', 'email'], 'required'],
            ['email', 'email'],
        ];
    }


    public static function test(){ return 'fasdfasdfasdfasdf'; }

}
