<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\entities\Carousel */

$this->title = '修改轮播: ' . $model->id;
$this->params['breadcrumbs'][] = ['label' => '轮播', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="carousel-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
