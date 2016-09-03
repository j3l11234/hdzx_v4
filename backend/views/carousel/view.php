<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

use common\models\entities\Carousel;

/* @var $this yii\web\View */
/* @var $model common\models\entities\Carousel */

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => '轮播', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="carousel-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('修改', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('删除', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to delete this item?',
                'method' => 'post',
            ],
        ]) ?>
    </p>
    
    <?php
        $statusTexts = Carousel::getStatusTexts();
    ?>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'title',
            'content',
            'picture',
            [
                'attribute' => 'status',
                'value' => $statusTexts[$model->status],
            ],
            'align',
            [
                'attribute' => 'created_at',
                'format' => ['datetime', 'php:Y-m-d H:i:s'],
            ],
            [
                'attribute' => 'updated_at',
                'format' => ['datetime', 'php:Y-m-d H:i:s'],
            ],
        ],
    ]) ?>

</div>
