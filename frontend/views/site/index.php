<?php

use yii\bootstrap\Carousel;
/* @var $this yii\web\View */

$this->title = '学活场地申请系统';
?>
<div class="site-index">
    <?php
    $items = [];
    foreach ($carousels as $carousel) {
        $items[] = [
            'content' => '<img src="images/carousels/'.$carousel->picture.'"/>',
            'caption' => '<h4>'.$carousel->title.'</h4><p>'.$carousel->content.'</p>',
            'options' => [],
        ];
    }
    ?>

    <?= Carousel::widget([
        'items' => $items,
        'controls' => [
            '<span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>',
            '<span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>'
        ],
        'options' => ['class' => 'slide'],
    ]);?>

</div>
