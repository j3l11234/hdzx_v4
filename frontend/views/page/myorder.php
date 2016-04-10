<?php

/* @var $this yii\web\View */

use frontend\assets\ReactAsset;

ReactAsset::register($this);
$this->registerJsFile('@web/js/myorder.js', ['depends'=>[ReactAsset::className()]]);
$this->title = '我的预约';
?>
<div id="myorder-page">
</div>
<script>
	_Server_Data_.start_date = '<?= $start_date ?>';
	_Server_Data_.end_date = '<?= $end_date ?>';
</script>
