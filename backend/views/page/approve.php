<?php

/* @var $this yii\web\View */
use backend\assets\ReactAsset;

$this->params['page'] = 'approve';
ReactAsset::register($this);

$this->title = '预约审批';
?>
<div id="approve-page">
</div>
<script>
	_Server_Data_.apprveType = '<?= $apprveType ?>';
</script>
