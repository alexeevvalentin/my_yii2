<?php

namespace app\models;


use Yii;
use yii\base\Model;
use yii\caching\MemCache;
use app\models\GenF;
use yii\data\Pagination;

class News extends Model
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

    public static function json_format_data($str){
        $str = '{"content":'.$str.'}';
        $obj = json_decode($str);
        return $obj;
    }

    public static function get_source_cache($url, $key_cache, $time){

        $memcache = Yii::$app->cache;
        $cache_key = $key_cache;
        $data_cache = $memcache->get($cache_key);
        if ($data_cache === false) {
            $data_fresh = @file_get_contents($url);
            $memcache->set($cache_key, $data_fresh, $time);
            return GenF::object_to_array(self::json_format_data($data_fresh));
        }else{
            return GenF::object_to_array(self::json_format_data($data_cache));
        }

    }

    public static function get_pages($url, $key_cache='', $time=''){
        if(is_array($url)){
            $file_content = $url;
            $pages = new Pagination([
                'totalCount' => count($file_content['content']),
                'forcePageParam' => true,
                'pageSizeParam' => false,
            ]);
            return $pages;
        }else{
            $file_content = News::get_source_cache($url, $key_cache, $time);
            $pages = new Pagination([
                'totalCount' => count($file_content['content']),
                'forcePageParam' => true,
                'pageSizeParam' => false,
            ]);
            return $pages;
        }
    }

}
