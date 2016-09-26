<?php

namespace common\models\entities;

use Yii;

/**
 * This is the model class for table "hdzx_setting".
 *
 * @property string $id
 * @property string $description
 * @property string $value
 * @property string $data
 */
class Setting extends \yii\db\ActiveRecord
{
    
    const ORDER_PAGE_TOOLTIP = 'order_page_tooltip';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%setting}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id'], 'required'],
            [['value', 'data'], 'string'],
            [['id','description'], 'string', 'max' => 255],
        ];
    }
    

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'description' => '描述',
            'value' => 'Value',
            'data' => 'Data',
        ];
    }
}
