<?php

/* @var $this \yii\web\View */
/* @var $content string */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;

use frontend\assets\AppAsset;
use common\services\SettingService;

AppAsset::register($this);
$this->params['dynamic'] = [];
?>
<?php $this->beginPage() ?>

<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge,chrome=1" >
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?= $this->renderFile('@app/views/layouts/header.php') ?>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>
<script>
    var _Server_Data_ = {};
    _Server_Data_.BASE_URL = '<?= Url::to(['/']) ?>';
</script>

<?php
    if (Yii::$app->user->isGuest) {
        $this->params['dynamic']['login'] = 'false';
        $this->params['dynamic']['user'] = '未登录';
    } else {
        $user = Yii::$app->user->identity->getUser();
        $this->params['dynamic']['login'] = 'true';
        $this->params['dynamic']['user'] = $user->username.' ('. $user->alias.')';
    }
    if ($this->beginCache('Main_Navbar')) {
?>
<nav id="navbar" class="navbar navbar-default navbar-fixed-top" role="navigation">
    <div class="container">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#w0-collapse">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="<?=Yii::$app->homeUrl?>"><?=Yii::$app->name?></a>
        </div>
        <div id="w0-collapse" class="collapse navbar-collapse">
            <ul class="navbar-nav navbar-right nav">
            <?php
                $navList = SettingService::getNavList();
                foreach ($navList['navMap']['0'] as $nav_id) {
                    $nav = $navList['navs'][$nav_id];      
                    if (isset($navList['navMap'][$nav_id])) {
                        echo Html::beginTag('li', ['id' => 'nav-item-'.$nav['html_id'], 'class' => 'dropdown nav-item']);
                        echo Html::beginTag('a',['href' => empty($nav['url']) ? '#': Url::to([$nav['url']]), 'class' => 'dropdown-toggle', 'data-toggle' => 'dropdown']);
                        echo $nav['name'];
                        echo Html::tag('b', '' ,[ 'class' => 'caret']);
                        echo Html::endTag('a');
                        echo Html::beginTag('ul', ['class' => 'dropdown-menu']);
                        foreach ($navList['navMap'][$nav_id] as $nav_id_2) {
                            $nav_2 = $navList['navs'][$nav_id_2];
                            echo Html::beginTag('li', ['id' => 'nav-item-'.$nav_2['html_id'], 'class' => 'nav-item']);
                            echo Html::tag('a', $nav_2['name'], ['href' => empty($nav_2['url']) ? '#': Url::to([$nav_2['url']])]);
                            echo Html::endTag('li');
                        }
                        echo Html::endTag('ul');
                        echo Html::endTag('li');
                    } else {
                        echo Html::beginTag('li', ['id' => 'nav-item-'.$nav['html_id'], 'class' => 'nav-item']);
                        echo Html::tag('a',$nav['name'],['href' => Url::to([$nav['url']])]);
                        echo Html::endTag('li');
                    }   
                }
            ?>
            <li class="dropdown">
                <a id="navbar-username" class="dropdown-toggle" href="<?=Url::to(["/login"])?>" data-toggle="dropdown"><?=$this->renderDynamic('return $this->params[\'dynamic\'][\'user\'];') ?><b class="caret"></b></a>
                <ul id="navbar-usermenu-nologin" class="dropdown-menu">
                    <li><a href="<?=Url::to(["/login"])?>" tabindex="-1">登录</a></li>
                    <li><a href="<?=Url::to(["/user/request-student-user"])?>" tabindex="-1">激活学生账户</a></li>
                    <li><a href="<?=Url::to([Yii::$app->params['backendUrl']])?>" tabindex="-1">进入后台系统</a></li>
                </ul>
                <ul id="navbar-usermenu-logined" class="dropdown-menu">
                    <li><a href="<?=Url::to(["/user/logout"])?>" data-method="post" tabindex="-1">注销</a></li>
                    <li><a href="<?=Url::to(["/user/request-password-reset"])?>" tabindex="-1">修改密码</a></li>
                    <li><a href="<?=Url::to([Yii::$app->params['backendUrl']])?>" tabindex="-1">进入后台系统</a></li>
                </ul>

            </li>
            <li id="navbar-user-logined" class="dropdown" style="display: none;">
                <a class="dropdown-toggle" href="#" data-toggle="dropdown"><?=$this->renderDynamic('return $this->params[\'dynamic\'][\'user\'];') ?><b class="caret"></b></a>
                
            </li>
            </ul>
        </div>
    </div>
</nav>
<script>
    (function() {
        var path = window.location.pathname;
        var index = path.indexOf('#');
        if (index !== -1) {
            path = path.substring(0, index);
        }

        var _navbarEl = document.getElementById('navbar');
        if (!_navbarEl) {
            return;
        }
        var _hrefEls = _navbarEl.getElementsByTagName('a');
        for (index in _hrefEls) {
            var _hrefEl = _hrefEls[index];
            var _parentEl = _hrefEl.parentNode;
            if (!_parentEl || _parentEl.className.indexOf('nav-item') === -1) {
                continue;
            }
            if (_hrefEl.getAttribute('href') == path) {
                _parentEl.className += ' active';
            }
        }
    })();
    (function() {
        var _nologinEl = document.getElementById('navbar-usermenu-nologin');
        var _loginedEl = document.getElementById('navbar-usermenu-logined');
        var _usernameEl = document.getElementById('navbar-username');
        if (!_nologinEl || !_loginedEl || !_usernameEl) {
            return;
        }
        if (<?=$this->renderDynamic('return $this->params[\'dynamic\'][\'login\'];') ?>) {
            removeElement(_nologinEl);
            _usernameEl.href = '#';
        } else {
            removeElement(_loginedEl);
        }
        
        function removeElement(_element){
            var _parentElement = _element.parentNode;
            if(_parentElement){
                _parentElement.removeChild(_element);  
            }
        }
    })();  
</script>
<?php $this->endCache(); } ?>

<div class="wrap">
    <?php if (isset($this->params['banner'])){ ?>
        <div class="banner">
            <h2><?= $this->params['banner'] ?></h2>
        </div>
    <?php } ?>
    <div class="container">
        <?= Breadcrumbs::widget([
            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
        ]) ?>
        <?= $content ?>
    </div>
    <div id="stick-alert"></div>
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
