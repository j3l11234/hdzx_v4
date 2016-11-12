<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model \frontend\models\ResetPasswordForm */

use yii\helpers\Html;
use common\widgets\Alert as MessAlert;

$this->context->layout = 'main';
$this->title = '操作结果';

?>
<?= MessAlert::widget() ?>
<div class="">
    <div class="row">
         <?= Helper::renderFlash() ?>
    </div>
</div>
<script>

</script>
