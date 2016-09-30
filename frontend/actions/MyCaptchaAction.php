<?php
namespace frontend\actions;

use Yii;
use yii\captcha\CaptchaAction;
use yii\helpers\Url;
use yii\web\Response;

/**
 * 定制的CaptchaAction
 */
class MyCaptchaAction extends CaptchaAction {

    /**
     * @inheritdoc
     */
    public function run() {
        if (Yii::$app->request->getQueryParam(self::REFRESH_GET_VAR) !== null) {
            // AJAX request for regenerating code
            $code = $this->getVerifyCode(true);
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                // we add a random 'v' parameter so that FireFox can refresh the image
                // when src attribute of image tag is changed
                'error' => 0,
                'url' => Url::to([$this->id, 'v' => uniqid()]),
            ];
        } else {
            $this->setHttpHeaders();
            Yii::$app->response->format = Response::FORMAT_RAW;
            return $this->renderImage($this->getVerifyCode());
        }
    }

    public function getCaptchaUrl() {
        return Url::to([$this->id, 'v' => uniqid()]);
    }
}
