<?php

use yii\helpers\Html;
use yii\grid\DataColumn;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\user\User */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Users', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Delete', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to delete this item?',
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'username',
            'auth_key',
            'password_hash',
            'password_reset_token',
            'dept_id',
            'email:email',
            'alias',
            'approve_dept:ntext',
            'privilege',
            'status',
            [
                'class' => DataColumn::className(),
                'attribute' => 'created_at',
                'format' => ['datetime', 'php:Y-m-d H:i:s'],
            ],
            [
                'class' => DataColumn::className(),
                'attribute' => 'updated_at',
                'format' => ['datetime', 'php:Y-m-d H:i:s'],
            ],
        ],
    ]) ?>

</div>
