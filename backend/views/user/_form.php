<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

use common\models\entities\BaseUser;

/* @var $this yii\web\View */
/* @var $model common\models\user\User */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="user-form">

    <?php 
        $form = ActiveForm::begin();
        $model->privilege = BaseUser::privilegeNum2List($model->privilege);
    ?>

    <?= $form->field($model, 'username')->textInput(['maxlength' => true]); ?>

    <?= $form->field($model, 'password')->textInput(['value' => ''])->hint('不修改密码请留空此项'); ?>
    
    <?= $form->field($model, 'email')->textInput() ?>

    <?= $form->field($model, 'alias')->textInput() ?>

    <?= $form->field($model, 'managers')->textInput(['value' => json_encode($model->managers)]) ?>
    
    <?= $form->field($model, 'privilege')->checkboxList(BaseUser::getPrivilegeTexts(),['separator' => '<br>']) ?>

    <?= $form->field($model, 'status')->dropDownList(BaseUser::getStatusTexts()) ?>

	<div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? '创建' : '更新', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
