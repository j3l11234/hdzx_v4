<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $user common\models\User */

$resetLink = Yii::$app->urlManager->createAbsoluteUrl(['user/verify-user', 'type'=>$type, 'token' => $user->password_reset_token]);
?>
<div>
    <p>您好 <?= Html::encode($user->alias) ?>,</p>

    <p>请点击以下链接以验证您的账号:</p>

    <p><?= Html::a(Html::encode($resetLink), $resetLink) ?></p>
</div>
