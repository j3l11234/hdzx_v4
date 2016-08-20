<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\user\User */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="user-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'username')->label('用户名')->textInput(['maxlength' => true]); ?>

    <?= $form->field($model, 'password')->label('密码')->textInput(['value' => ''])->hint('不修改密码请留空此项'); ?>
    
    <?= $form->field($model, 'email')->label('邮箱')->textInput() ?>

    <?= $form->field($model, 'alias')->label('显示名')->textInput() ?>

    <?= $form->field($model, 'managers')->label('负责人审批者')->textInput(['value' => json_encode($model->managers)]) ?>
    
    <?= $form->field($model, 'privilege')->label('权限')->textInput() ?>

    <?= $form->field($model, 'status')->label('状态')->textInput() ?>

	<div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? '创建' : '更新', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
