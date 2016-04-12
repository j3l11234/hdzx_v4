<?php

/* @var $this yii\web\View */
use backend\assets\ReactAsset;

ReactAsset::register($this);
$this->registerJsFile('@web/js/lock.js', ['depends'=>[ReactAsset::className()]]);
$this->title = '房间锁';
?>
<div id="lock-page">
</div>
<script>
	_Server_Data_.isAdmin = '<?= $isAdmin ?>';
</script>