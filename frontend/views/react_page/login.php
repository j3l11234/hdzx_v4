<?php

/* @var $this yii\web\View */
use frontend\assets\ReactAsset;

$this->params['page'] = 'login';
ReactAsset::register($this);

$this->title = '用户登录';
?>
<div id="login-page">
</div>
