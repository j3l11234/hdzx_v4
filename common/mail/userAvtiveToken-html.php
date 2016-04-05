<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $user common\models\User */

$resetLink = Yii::$app->urlManager->createAbsoluteUrl(['user/active-user', 'token' => $user->password_reset_token]);
?>
<div>
    <p>您好 <?= Html::encode($user->alias) ?>,</p>

    <p>请点击以下链接以激活您的账户:</p>

    <p><?= Html::a(Html::encode($resetLink), $resetLink) ?></p>
</div>
