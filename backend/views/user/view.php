<?php

use yii\helpers\Html;
use yii\grid\DataColumn;
use yii\widgets\DetailView;

use common\models\entities\BaseUser;

/* @var $this yii\web\View */
/* @var $model common\models\user\User */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Users', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-view">

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
        $privilege = '';
        $privList = BaseUser::privilegeNum2List($model->privilege);
        $privileges = BaseUser::getPrivilegeTexts();
        foreach ($privList as  $privNum) {
            $privilege .= $privileges[$privNum].', ';
        }
        $statusTexts = BaseUser::getStatusTexts();
    ?>
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            [
                'label' => '类型',
                'value' => $model->isStudent() ? '学生用户' : '普通用户',
            ],
            'username',
            'auth_key',
            'password_hash',
            'password_reset_token',
            'email:email',
            'alias',
            [
                'attribute' => 'managers',
                'value' => json_encode($model->managers),
            ],
            [
                'attribute' => 'privilege',
                'value' => $privilege,
            ],
            [
                'attribute' => 'status',
                'value' => $statusTexts[$model->status],
            ],
            [
                'attribute' => 'usage_limit',
                'value' => $model->usage_limit !== NULL ? json_encode($model->usage_limit) : '默认限额',
            ],
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
