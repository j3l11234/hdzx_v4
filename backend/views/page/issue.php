<?php

/* @var $this yii\web\View */
use backend\assets\ReactAsset;

$this->params['page'] = 'issue';
ReactAsset::register($this);

$this->title = '开门条发放';
?>
<div id="issue-page">
</div>