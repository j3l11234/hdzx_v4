<?php

/* @var $this \yii\web\View */
/* @var $content string */

use backend\assets\AppAsset;
use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use common\widgets\Alert;
use common\models\entities\BaseUser;

AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
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
        'brandLabel' => '学活场地申请后台系统',
        'brandUrl' => Yii::$app->homeUrl,
        'options' => [
            'class' => 'navbar-inverse navbar-fixed-top',
        ],
    ]);
    $menuItems = [
        ['label' => 'Home', 'url' => ['/site/index']],
    ];
    
    if (Yii::$app->user->isGuest) {
        $menuItems[] = [
            'label' => '未登录',
            'url' => ['/user/login'],
        ];
    } else {
        $menuItems[] = [
            'label' => '审批预约',
            'items'=>[
                ['label' => '自动审批', 'url' => ['/approve/auto']],
                ['label' => '负责人审批', 'url' => ['/approve/manager']],
                ['label' => '校级审批', 'url' => ['/approve/school']],
            ],
        ];

        $user = Yii::$app->user->getIdentity()->getUser();
        if ($user->checkPrivilege(BaseUser::PRIV_ADMIN)){
            $menuItems[] = [
                'label' => '用户管理',
                'items'=>[
                    ['label' => '普通用户', 'url' => ['/user/index']],
                    ['label' => '学生用户', 'url' => ['/user/student']],
                ],
            ];
            $menuItems[] = ['label' => '房间锁', 'url' => ['/lock']];
        }

        $menuItems[] = [
            'label' => Yii::$app->user->identity->username.' ('. Yii::$app->user->identity->alias.')',
            'items'=>[
                ['label' => '注销', 'url' => ['/user/logout'], 'linkOptions' => ['data-method' => 'post']],
                ['label' => '修改密码', 'url' => ['/user/request-password-reset']],
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
        <p class="pull-left">&copy; My Company <?= date('Y') ?></p>

        <p class="pull-right"><?= Yii::powered() ?></p>
    </div>
</footer>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
