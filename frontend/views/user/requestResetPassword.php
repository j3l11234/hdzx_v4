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
				    'body' => '    请输入您的学号和验证码，我们将会向您的邮箱发送一封验证邮件，请按照提示修改您的密码。',
				]) ?>
            	<?= $form->field($model, 'username')->label('学号')->textInput() ?>
				<?= $form->field($model, 'captcha', [
					'enableClientValidation' => false,
				])
				->label('验证码')
				->widget(Captcha::className(),[
			       'template' => "{input} {image}",
			       'imageOptions' => ['alt' => '验证码'],
			       'captchaAction' => 'user/captcha',
				]) ?>

                <div class="form-group">
                    <?= Html::submitButton('提交', ['class' => 'btn btn-primary']) ?>
                </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
