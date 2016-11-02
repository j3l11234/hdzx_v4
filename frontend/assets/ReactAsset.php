<?php
/**
 * @link http://www.j3l11234.com/
 * @copyright Copyright (c) 2015 j3l11234
 * @author j3l11234@j3l11234.com
 */

namespace frontend\assets;

use Yii;
use yii\web\AssetBundle;

class ReactAsset extends AssetBundle{
    public $sourcePath = '@vendor/html_assets';

    public $css = [
    ];

    public $js = [
        'js/common.js',
        'js/lock.js',
        'js/login.js',
        'js/myorder.js',
        'js/order.js',
    ];

    public $depends = [
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
