<?php
namespace common\behaviors;

use Yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;

/**
 * JsonBehavior
 * model的json数据整理
 */
class JsonBehavior extends Behavior {
    public $attributes = [];

    /**
     * @inheritdoc
     */
    public function events() {
        return [
            ActiveRecord::EVENT_AFTER_FIND => 'jsonToArray',
            ActiveRecord::EVENT_BEFORE_INSERT => 'arrayToJson',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'arrayToJson',
            ActiveRecord::EVENT_AFTER_INSERT => 'jsonToArray',
            ActiveRecord::EVENT_AFTER_UPDATE => 'jsonToArray',
        ];
    }

    /**
     * 将json文本转换成Array对象
     *
     * @return null
     */
    public function jsonToArray($event) {
        foreach ($this->attributes as $attribute) {
            if (is_array($attribute)){
                $attributeName = $attribute['attribute'];
                if ($attribute['jsonToArray'] !== null) {
                    $this->owner->$attributeName = call_user_func($attribute['jsonToArray'], $this->owner, $attributeName);
                } else {
                    $this->owner->$attributeName = json_decode($this->owner->$attributeName, true);
                }
            } else {
                $this->owner->$attribute = json_decode($this->owner->$attribute, true);
            }
        }
    }

    /**
     * 将Array对象转换成json文本
     *
     * @return null
     */
    public function arrayToJson($event) {
        foreach ($this->attributes as $attribute) {
            if (is_array($attribute)){
                $attributeName = $attribute['attribute'];
                if ($attribute['arrayToJson'] !== null) {
                    $this->owner->$attributeName = call_user_func($attribute['arrayToJson'], $this->owner, $attributeName);
                } else {
                    $this->owner->$attributeName = json_encode($this->owner->$attributeName);
                }
            } else {
                $this->owner->$attribute = json_encode($this->owner->$attribute);
            }
        }
    }

}