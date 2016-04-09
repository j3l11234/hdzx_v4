<?php

/* @var $this yii\web\View */

use frontend\assets\ReactAsset;

ReactAsset::register($this);
$this->registerJsFile('@web/js/order.js', ['depends'=>[ReactAsset::className()]]);
$this->title = '房间预约';
?>
<div id="order-page">

</div>
