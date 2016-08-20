<?php

use yii\helpers\Html;
use yii\grid\DataColumn;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Users';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Create User', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'id',
            'username',
            //'auth_key',
            //'password_hash',
            //'password_reset_token',
            'email:email',
            'alias',
            [
                'class' => DataColumn::className(),
                'attribute' => 'managers',
                'label' => '负责人审批者',
                'content' => function ($model, $key, $index, $column){
                    return json_encode($model->managers);
                },
            ],
            'privilege',
            'status',
            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>

</div>
