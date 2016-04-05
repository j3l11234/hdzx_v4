<?php

/* @var $this yii\web\View */
/* @var $user common\models\User */

$resetLink = Yii::$app->urlManager->createAbsoluteUrl(['user/active-user', 'token' => $user->password_reset_token]);
?>
您好 <?= $user->username ?>,

请点击以下链接以激活您的账户:

<?= $resetLink ?>
