<?php

/* @var $this yii\web\View */
use frontend\assets\ReactAsset;

ReactAsset::register($this);
$this->registerJsFile('@web/js/login.js', ['depends'=>[ReactAsset::className()]]);
$this->title = '用户登录';
?>
<div id="login-page">
</div>
