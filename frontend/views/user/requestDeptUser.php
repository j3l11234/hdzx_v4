<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model \frontend\models\SignupForm */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\bootstrap\Alert;
use yii\captcha\Captcha;

$this->title = '激活社团账户';
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
                    <li>用户名：3-15位的中文、英文字母、数字和下划线组合，建议使用社团名称的简拼</li>
                    <li>姓名：请按照社团名称格式输入</li>
                    <li>邮箱：请使用交大学号邮箱</li>
                    </ul>
                    <br />
                    <p>&nbsp;&nbsp;我们将会向该邮箱发送一封验证邮件，请按照提示完成您的注册流程，验证成功后，请等待管理员审批。</p>',
                ]) ?>
                <?= $form->field($model, 'username') ?>
                <?= $form->field($model, 'alias') ?>
                <?= $form->field($model, 'email') ?>
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
