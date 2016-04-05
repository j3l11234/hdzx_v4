<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model \frontend\models\SignupForm */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\bootstrap\Alert;
use yii\captcha\Captcha;

$this->title = '用户激活';
$this->params['breadcrumbs'][] = $this->title;
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
                    'body' => '&nbsp;&nbsp;&nbsp;&nbsp;请输入您的学号，我们将会向您的校内邮箱发送一封验证邮件。请按照提示完成您的注册流程',
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
