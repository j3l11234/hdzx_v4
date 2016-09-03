<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model common\models\entities\Carousel */

$this->title = '创建轮播';
$this->params['breadcrumbs'][] = ['label' => '轮播', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="carousel-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
