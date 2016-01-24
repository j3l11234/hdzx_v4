<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\user\User */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="user-form">

    <?php $form = ActiveForm::begin(); ?>

    <?php $usernameField = $form->field($model, 'username'); ?>
    <?php $passwordField = $form->field($model, 'password'); ?>
    <?php if ($mode == 'create'): ?>
        <?= $usernameField->textInput(['maxlength' => true]) ?>
        <?= $passwordField->textInput() ?>  
    <?php else: ?>
        <?= $usernameField->textInput(['maxlength' => true])->hint("用户名将不会被修改") ?>
        <?= $passwordField->textInput()->hint("留空则不修改密码") ?>
    <?php endif; ?>

    <?= $form->field($model, 'dept_id')->textInput() ?>

    <?= $form->field($model, 'email')->textInput() ?>

    <?= $form->field($model, 'alias')->textInput() ?>

    <?= $form->field($model, 'approve_dept')->textInput() ?>
    
    <?= $form->field($model, 'privilege')->textInput() ?>

    <?= $form->field($model, 'status')->textInput() ?>

	<div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? '创建' : '更新', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
