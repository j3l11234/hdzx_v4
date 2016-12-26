<?php

/* @var $this yii\web\View */

use frontend\assets\VueAsset;
$this->params['page'] = 'apply';
VueAsset::register($this);
$this->title = '房间申请';
$this->params['banner'] = '房间申请';
?>

<div id="apply-page">
</div>
