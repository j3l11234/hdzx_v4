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
     * Gets the verification code.
     * @param bool $regenerate whether the verification code should be regenerated.
     * @return string the verification code.
     */
    public function getVerifyCode($regenerate = false)
    {
        if ($this->fixedVerifyCode !== null) {
            return $this->fixedVerifyCode;
        }
        $session = Yii::$app->getSession();
        $session->open();
        $name = $this->getSessionKey();
        if ($session[$name] === null || $regenerate) {
            $session[$name] = $this->generateVerifyCode();
            $session[$name . 'count'] = 1;
            $session[$name . 'time'] = time();
        }
        return $session[$name];
    }

    /**
     * Validates the input to see if it matches the generated code.
     * @param string $input user input
     * @param bool $caseSensitive whether the comparison should be case-sensitive
     * @return bool whether the input is valid
     */
    public function validate($input, $caseSensitive)
    {
        $code = $this->getVerifyCode();
        $valid = $caseSensitive ? ($input === $code) : strcasecmp($input, $code) === 0;
        $session = Yii::$app->getSession();
        $session->open();

        //临时解决方案
        $timeout = false;
        $name = $this->getSessionKey() . 'time';
        if ($session[$name] < strtotime('07:00:00') && time() >= strtotime('07:00:00')) {
            $timeout = true;
            $valid = false;
        }

        $name = $this->getSessionKey() . 'count';
        $session[$name] = $session[$name] + 1;
        if ($valid || $session[$name] > $this->testLimit && $this->testLimit > 0 || $timeout) {
            $this->getVerifyCode(true);
        }
        return $valid;
    }


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
