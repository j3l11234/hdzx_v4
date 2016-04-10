<?php

/* @var $this yii\web\View */

use frontend\assets\ReactAsset;

ReactAsset::register($this);
$this->registerJsFile('@web/js/order.js', ['depends'=>[ReactAsset::className()]]);
$this->title = '房间预约';
?>
<div id="order-page">
</div>
<script>
	_Server_Data_.start_date = '<?= $start_date ?>';
	_Server_Data_.end_date = '<?= $end_date ?>';
</script>
