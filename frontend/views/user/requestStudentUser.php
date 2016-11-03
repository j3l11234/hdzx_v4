<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model \frontend\models\SignupForm */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\bootstrap\Alert;
use yii\captcha\Captcha;
use common\helpers\Helper;

$this->title = '激活学生账户';
?>
<div class="site-signup">
    <h1><?= Html::encode($this->title) ?></h1>
    <div class="row">
        <div class="col-lg-5">
            <?php $form = ActiveForm::begin(['id' => 'form-signup']); ?>
                <?= Helper::renderFlash() ?>
                <br />
                <?= Alert::widget([
                    'options' => [
                        'class' => 'alert-info',
                    ],
                    'body' => '<ul>
                    <li>学号：请输入您的学号</li>
                    <li>姓名：请输入您的姓名</li>
                    <li>验证码：请输入您在下方看到的验证码</li>
                    </ul>
                    <br />
                    <p>&nbsp;&nbsp;&nbsp;&nbsp;在填写完成以下内容后，我们将会向您的邮箱发送一封验证邮件，请点击邮件里的验证链接以完成您的注册流程。</p>
                    <p>&nbsp;&nbsp;&nbsp;&nbsp;如果您看不到验证码，请移步意见反馈反映这个问题并告知您的操作系统和浏览器版本。</p>',
                ]) ?>
                <br />
                <?= $form->field($model, 'username')->label('学号') ?>
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
