<?php

namespace common\models\entities;

use Yii;

/**
 * This is the model class for table "{{%dept}}".
 *
 * @property integer $id
 * @property string $name
 * @property integer $align
 * @property integer $created_at
 * @property integer $updated_at
 */
class Department extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%dept}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'align', 'created_at', 'updated_at'], 'required'],
            [['align', 'created_at', 'updated_at'], 'integer'],
            [['name'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'align' => 'Align',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
