<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\bootstrap\Alert;

use common\models\entities\BaseUser;

/* @var $this yii\web\View */
/* @var $model common\models\user\User */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="user-form">

    <?php 
        $form = ActiveForm::begin();
        $model->privilege = BaseUser::privilegeNum2List($model->privilege);
    ?>
    <?= $form->field($model, 'username')->textInput(['maxlength' => true]); ?>

    <?= $form->field($model, 'password')->textInput(['value' => ''])->hint('不修改密码请留空此项'); ?>
    
    <?= $form->field($model, 'email')->textInput() ?>

    <?= $form->field($model, 'alias')->textInput() ?>

    <?= $form->field($model, 'managers')->textInput(['value' => json_encode($model->managers)]) ?>
    
    <?= $form->field($model, 'privilege')->checkboxList(BaseUser::getPrivilegeTexts(),['separator' => '<br>']) ?>

    <?= $form->field($model, 'status')->dropDownList(BaseUser::getStatusTexts()) ?>

    <?= $form->field($model, 'usage_limit')->textArea(['value' => json_encode($model->usage_limit)]) ?>
    <?= Alert::widget([
        'options' => [
            'class' => 'alert-info',
        ],
        'body' => '<p>房间使用限额为空或者null时，该用户使用默认限额。</p>
例如，以下示例的含义为：对于301、302房间，每周限制总和为21小时，对于403、440、441、603房间，每月限制总和为70小时。<br>
<pre>
[
    {
        "rooms": [301,302],
        "type": "week",
        "max": 21
    },
    {
        "rooms": [403,440,441,603],
        "type": "month",
        "max": 70
    }
]
</pre>
',
    ]) ?>

    <br>
	<div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? '创建' : '更新', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
