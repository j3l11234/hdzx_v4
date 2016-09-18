<?php
/**
 * @link http://www.j3l11234.com/
 * @copyright Copyright (c) 2015 j3l11234
 * @author j3l11234@j3l11234.com
 */

namespace backend\assets;

use Yii;
use yii\web\AssetBundle;

class ReactAsset extends AssetBundle{
    public $sourcePath = '@vendor/html_assets';

    public $css = [
    ];
    public $js = YII_ENV_DEV ? [
        'js/common.js',
        'js/approve.js',
        'js/issue.js',
        'js/lock.js',
    ]:[
        'js/common.js',
        'js/approve.js',
        'js/issue.js',
        'js/lock.js',
    ];
    public $depends = [
        'yii\web\JqueryAsset',
        'yii\bootstrap\BootstrapPluginAsset',
    ];


    public function registerAssetFiles($view)
    {
        $this->js = [
            'js/common.js',
        ];
        if (isset($view->params['page'])) {
            $this->js[] = 'js/'.$view->params['page'].'.js';
        }
        
        parent::registerAssetFiles($view);
    }
}
