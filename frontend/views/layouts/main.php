<?php

/* @var $this \yii\web\View */
/* @var $content string */

use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use frontend\assets\AppAsset;
use common\widgets\Alert;

AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="renderer" content="webkit">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?= $this->renderFile('@app/views/layouts/header.php') ?>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>
<script>
    var _Server_Data_ = {};
    _Server_Data_.BASE_URL = '<?= Yii::$app->urlManager->createUrl('/') ?>';
</script>
<div class="wrap">
    <?php
    NavBar::begin([
        'brandLabel' => Yii::$app->name,
        'brandUrl' => Yii::$app->homeUrl,
        'options' => [
            'class' => 'navbar-inverse navbar-fixed-top',
        ],
    ]);
    $menuItems = [
        ['label' => '房间预约', 'url' => ['/order']],
        ['label' => '我的预约', 'url' => ['/myorder']],
        ['label' => '房间锁', 'url' => ['/lock']],
        ['label' => '意见反馈', 'url' => ['/site/contact']],
    ];
    if (Yii::$app->user->isGuest) {
        $menuItems[] = [
            'label' => '未登录',
            'items'=>[
                ['label' => '登录', 'url' => ['/login']],
                ['label' => '激活学生账户', 'url' => ['/user/request-student-user']],
                ['label' => '进入后台系统', 'url' => [Yii::$app->params['backendUrl']]],
            ],
        ];
    } else {
        $menuItems[] = [
            'label' => Yii::$app->user->identity->username.' ('. Yii::$app->user->identity->alias.')',
            'items'=>[
                ['label' => '注销', 'url' => ['/user/logout'], 'linkOptions' => ['data-method' => 'post']],
                ['label' => '修改密码', 'url' => ['/user/request-password-reset']],
                ['label' => '进入后台系统', 'url' => [Yii::$app->params['backendUrl']]],
            ],
        ];
    }

    echo Nav::widget([
        'options' => ['class' => 'navbar-nav navbar-right'],
        'route' => Yii::$app->request->getPathInfo(),
        'items' => $menuItems,
    ]);
    NavBar::end();
    ?>

    <div class="container-fluid">
        <?= Breadcrumbs::widget([
            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
        ]) ?>
        <?= Alert::widget() ?>
        <?= $content ?>
    </div>
</div>

<footer class="footer">
    <div class="container">
        <p class="pull-left">&copy; 北京交通大学 学生活动服务中心 <?= date('Y') ?></p>
        <p class="pull-right">powered by <a href="http://blog.j3l11234.com">j3l11234</a></p>
    </div>
</footer>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
