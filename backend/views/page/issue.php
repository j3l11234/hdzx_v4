<?php

/* @var $this yii\web\View */
use backend\assets\ReactAsset;

ReactAsset::register($this);
$this->registerJsFile('@web/js/issue.js', ['depends'=>[ReactAsset::className()]]);
$this->title = '开门条发放';
?>
<div id="issue-page">
</div>