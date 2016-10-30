<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model common\models\entities\Navigation */

$this->title = 'Create Navigation';
$this->params['breadcrumbs'][] = ['label' => 'Navigations', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="navigation-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
