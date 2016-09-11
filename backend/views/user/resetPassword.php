<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model \frontend\models\ResetPasswordForm */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\bootstrap\Alert;
use yii\captcha\Captcha;

$this->title = '申请重设密码';
?>
<div class="site-reset-password">
    <h1><?= Html::encode($this->title) ?></h1>

    <div class="row">
        <div class="col-lg-5">
            <?php $form = ActiveForm::begin(['id' => 'reset-password-form']); ?>
				<?= Alert::widget([
				    'options' => [
				        'class' => 'alert-info',
				    ],
				    'body' => '    请输入您的旧密码和新密码',
				]) ?>
            	<?= $form->field($model, 'password_old')->passwordInput() ?>
            	<?= $form->field($model, 'password')->passwordInput() ?>
            	<?= $form->field($model, 'password_repeat')->passwordInput() ?>
                <div class="form-group">
                    <?= Html::submitButton('提交', ['class' => 'btn btn-primary']) ?>
                </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
