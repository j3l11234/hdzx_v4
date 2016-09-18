<?php
/**
 * @link http://www.j3l11234.com/
 * @copyright Copyright (c) 2015 j3l11234
 * @author j3l11234@j3l11234.com
 */

namespace frontend\assets;

use yii\web\AssetBundle;

class AppAsset extends AssetBundle
{
    public $sourcePath = '@vendor/html_assets';

    public $css = [
        'css/app.css',
    ];
    public $js = [
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
    ];
}
