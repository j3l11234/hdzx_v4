<?php
/**
 * @link http://www.j3l11234.com/
 * @copyright Copyright (c) 2015 j3l11234
 * @author j3l11234@j3l11234.com
 */

namespace common\models\entities;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%nav}}".
 *
 * @property integer $id
 * @property string $url
 * @property string $name
 * @property integer $align
 * @property integer $parent
 */
class Navigation extends ActiveRecord {
    /**
     * 状态 禁用
     */
    const STATUS_DISABLED = 0;
    /**
     * 状态 正常显示
     */
    const STATUS_ENABLE = 1;


    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%nav}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['url', 'name'], 'required'],
            [['align', 'parent'], 'integer'],
            [['url', 'name'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'url' => 'Url',
            'name' => 'Name',
            'align' => 'Align',
            'parent' => 'Parent',
        ];
    }
}
