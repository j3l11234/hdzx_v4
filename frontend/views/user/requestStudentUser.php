<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model \frontend\models\SignupForm */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\bootstrap\Alert;
use yii\captcha\Captcha;

$this->title = '激活学生账户';
?>
<div class="site-signup">
    <h1><?= Html::encode($this->title) ?></h1>
    <div class="row">
        <div class="col-lg-5">
            <?php $form = ActiveForm::begin(['id' => 'form-signup']); ?>
                <?= Alert::widget([
                    'options' => [
                        'class' => 'alert-info',
                    ],
                    'body' => '<ul>
                    <li>用户名：请输入您的学号</li>
                    <li>姓名：请输入您的姓名</li>
                    </ul>
                    <br />
                    <p>&nbsp;&nbsp;我们将会向您的邮箱发送一封验证邮件，请按照提示完成您的注册流程。</p>',
                ]) ?>
                <?= $form->field($model, 'username') ?>
                <?= $form->field($model, 'alias') ?>
                <?= $form->field($model, 'password')->passwordInput() ?>
                <?= $form->field($model, 'captcha', [
                    'enableClientValidation' => false,
                ])->widget(Captcha::className(),[
                   'template' => "{input} {image}",
                   'imageOptions' => ['alt' => '验证码'],
                   'captchaAction' => 'user/captcha',
                ]) ?>           
                <div class="form-group">
                    <?= Html::submitButton('激活', ['class' => 'btn btn-primary', 'name' => 'signup-button']) ?>
                </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
