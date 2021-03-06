<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model \frontend\models\ResetPasswordForm */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use common\helpers\Helper;

$this->title = '重设密码';
?>
<div class="site-reset-password">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>请输入新的密码:</p>

    <div class="row">
        <div class="col-lg-5">
            <?php $form = ActiveForm::begin(['id' => 'reset-password-form']); ?>
                <?= Helper::renderFlash() ?>
                <br />
                
                <?= $form->field($model, 'password')->label('新密码')->passwordInput() ?>

                <div class="form-group">
                    <?= Html::submitButton('提交', ['class' => 'btn btn-primary']) ?>
                </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
