<?php

/* @var $this yii\web\View */
use frontend\assets\ReactAsset;

$this->params['page'] = 'lock';
ReactAsset::register($this);

$this->title = '房间锁';
?>
<div id="lock-page">
</div>
<script>
	_Server_Data_.type = '<?= $type ?>';
</script>