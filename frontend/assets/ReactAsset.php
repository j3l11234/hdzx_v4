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
        'js/react_common.js',
        'js/react_lock.js',
        'js/react_login.js',
        'js/react_myorder.js',
        'js/react_order.js',
    ];

    public $depends = [
        'yii\web\JqueryAsset',
        'yii\bootstrap\BootstrapPluginAsset',
    ];

    public function registerAssetFiles($view)
    {
        $_js = $this->js;
        $this->js = [
            'js/react_common.js',
        ];
        if (isset($view->params['page'])) {
            $jsFile = 'js/react_'.$view->params['page'].'.js';
            if (in_array($jsFile, $_js)) {
                $this->js[] = $jsFile;
            }
        }
        
        parent::registerAssetFiles($view);
    }
}
