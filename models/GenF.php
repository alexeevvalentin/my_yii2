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
class GenF extends Model
{

    public function rules()
    {
        /*
        return [
            [['name', 'email'], 'required'],
            ['email', 'email'],
        ];
        */
    }

    public static function is_json_string($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    public static function index_of($source, $search){
        if(strpos($source, $search)===false){
            return -1;
        }else{
            return strpos($source, $search);
        }
    }
    public static function last_index_of($source, $search){
        if(strrpos($source, $search)===false){
            return -1;
        }else{
            return strrpos($source, $search);
        }
    }
    public static function get_file_source($file_path){
        return file_get_contents($file_path);
    }
    public static function set_file_source($file_path='./test_create_php.txt', $content='content'){
        file_put_contents($file_path, $content);
    }

    public static function array_to_object($array) {
        $obj = new \stdClass;
        foreach($array as $k => $v) {
            if(strlen($k)) {
                if(is_array($v)) {
                    $obj->{$k} = self::array_to_object($v); //RECURSION
                } else {
                    $obj->{$k} = $v;
                }
            }
        }
        return $obj;
    }

    public static function array_to_object_keystr($array, $delim='____') {
        $obj = new \stdClass;
        foreach($array as $k => $v) {
            if(strlen($k)) {
                if(is_array($v)) {
                    $obj->{$k.$delim} = self::array_to_object_keystr($v, $delim); //RECURSION
                } else {
                    $obj->{$k.$delim} = $v;
                }
            }
        }
        return $obj;
    }

    public static function object_to_array($obj) {
        if(is_object($obj)) $obj = (array) $obj;
        if(is_array($obj)) {
            $new = array();
            foreach($obj as $key => $val) {
                $new[$key] = self::object_to_array($val);
            }
        }
        else $new = $obj;
        return $new;
    }

    public static function get_file_name_dir($path_dir){
        if(file_exists($path_dir)){
            $dir = opendir($path_dir);
            $retArr = array();
            while($file = readdir($dir)){
                if(file_exists($path_dir . $file)){
                    if($file == '.' || $file == '..' || is_dir($path_dir . $file)){continue;}
                    $retArr['full'][]=$path_dir . $file;
                    $retArr['short'][]=$file;
                }
            }
            return $retArr;
        }
    }

    public static function no_spec_symb(&$str){
        $str = preg_replace("/&#?[a-z0-9]+;/i"," ",$str);
        $str = preg_replace("/(?![.=$'€%-])\p{P}/u", "", $str);
        $str = preg_replace('| +|', ' ', $str);
        $str = str_replace(".", "", $str);
        $str = trim($str);
    }

    public static function no_dopspec_symb(&$str){
        if (preg_match("/[%D]/i", $str)) {
            $str = '';
        }
    }

    public static function destroy_str_width_spec_symb(&$str){
        if (preg_match("/[\$,<,>,=]/i", $str)) {
            $str = '';
        }
    }
    public static function no_mnemonic_symb(&$str){
        $str = preg_replace("/&#?[a-z0-9]+;/i"," ",$str);
        $str = preg_replace('| +|', ' ', $str);
        $str = str_replace(".", "", $str);
        $str = trim($str);
    }
    public static function no_prob_symb(&$str){
        $str = preg_replace('| +|', ' ', $str);
        $str = trim($str);
    }

}
