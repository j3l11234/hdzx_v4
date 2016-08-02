<?php

namespace common\models\entities;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "hdzx_carousel".
 *
 * @property integer $id
 * @property string $title
 * @property string $content
 * @property string $picture
 * @property integer $align
 * @property integer $created_at
 * @property integer $updated_at
 */
class Carousel extends ActiveRecord {
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hdzx_carousel';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['align', 'created_at', 'updated_at'], 'integer'],
            [['title', 'content', 'picture'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'content' => 'Content',
            'picture' => 'Picture',
            'align' => 'Align',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
