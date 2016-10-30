<?php
/**
 * @link http://www.j3l11234.com/
 * @copyright Copyright (c) 2015 j3l11234
 * @author j3l11234@j3l11234.com
 */

namespace common\services;

use Yii;
use yii\base\Component;
use yii\caching\TagDependency;
use common\helpers\HdzxException;
use common\helpers\Error;
use common\models\entities\Setting;
use common\models\entities\Navigation;

/**
 * 设置服务类
 */
class SettingService extends Component {

    /**
     * 根据取得对应的设置
     * (带缓存)
     *
     * @param boolean $onlyId 仅获取id
     * @param $useCache 是否使用缓存(默认为是)
     * @return Array 如果onlyId未真，返回lock_id的列表，否则返回Lock的Map
     */
    public static function getSetting($key = FALSE, $useCache = TRUE) {
        $setting;

        //读取缓存
        $cacheMiss;
        if ($useCache) {
            $cacheKey = 'Setting_'.$key;
            $cacheData = Yii::$app->cache->get($cacheKey);
            if ($cacheData == null) {
                Yii::trace($cacheKey.':缓存失效', '数据缓存');
                $cacheMiss = TRUE;
            } else {
                Yii::trace($cacheKey.':缓存命中', '数据缓存');
                $setting = $cacheData;
                $cacheMiss = FALSE;
            }
        } else {
            $cacheMiss = TRUE;
        }
        if ($cacheMiss) {
            $setting = Setting::find()->select(['value', 'data',])
                ->where(['id' => $key])->asArray()->one();

            if (!empty($setting)) {
                //写入缓存
                $cacheKey = 'Setting_'.$key;
                Yii::$app->cache->set($cacheKey, $setting,
                    Yii::$app->params['cache.duration'],
                    new TagDependency(['tags' => [$cacheKey, 'Setting']]));
                Yii::trace($cacheKey.':写入缓存', '数据缓存'); 
            } else {
                $setting = [
                    'value' => NULL,
                    'data' => NULL,
                ];
            }
        }

        return $setting;
    }

    /**
     * 获取导航条内容
     * (带缓存)
     *
     * @param boolean $onlyId 仅获取id
     * @param $useCache 是否使用缓存(默认为是)
     * @return Array 如果onlyId未真，返回lock_id的列表，否则返回Lock的Map
     */
    public static function getNavList() {
        $navMap = ['0' => []];
        $navs = [];
        foreach (Navigation::find()
            ->where(['status' => Navigation::STATUS_ENABLE])
            ->select(['id', 'url', 'html_id', 'name', 'parent_id'])
            ->orderBy('align')
            ->asArray()
            ->each(100) as $nav) {
            $parent_id = $nav['parent_id'];
            if (empty($parent_id)) {
                $parent_id = '0';
            }
            if(!isset($navMap[$parent_id])){
                $navMap[$parent_id] = [];
            }
            $navMap[$parent_id][] = $nav['id'];
            $navs[$nav['id']] = $nav;
        }

        $navList = [
            'navMap' => $navMap,
            'navs' => $navs,
        ];
        return $navList;
    }
}