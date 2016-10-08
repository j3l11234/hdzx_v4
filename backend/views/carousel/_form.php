<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

use common\models\entities\Carousel;

/* @var $this yii\web\View */
/* @var $model common\models\entities\Carousel */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="carousel-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'title')->textInput() ?>

    <?= $form->field($model, 'content')->textArea([
        'rows' => 5,
    ]) ?>
    
    <?php 
    $path = Yii::getAlias('@frontend/web/images/carousels');
    $files = [];
    if(is_dir($path)) {        
        if($files = scandir($path)) {        
            $files = array_slice($files,2);
        }        
    }
    $pictures = array_combine($files, $files);
    ?>

    <?= $form->field($model, 'picture')->dropDownList($pictures) ?>
    
    <?= $form->field($model, 'status')->dropDownList(Carousel::getStatusTexts()) ?>

    <?= $form->field($model, 'align')->textInput() ?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
