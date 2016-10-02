<?php
namespace frontend\actions;

use Yii;
use yii\base\Action;
use yii\helpers\Url;
use yii\web\Response;

/**
 * 负责生成pdf的action
 */
class CreatePdfAction extends Action {

    const TOKEN_GET_VAR = 'token';

    /**
     * @var token有效时间 单位为秒，默认为60，0为不限制
     */
    public $expire = 60;

    private $errorMessage = '';

    public function setPdfData($data) {
        $token = $this->generateToken($data);

        $session = Yii::$app->getSession();
        $session->open();
        $name = $this->getSessionKey();
        $session[$name . 'data'] = $data;
        $session[$name . 'token'] = $token;
        $session[$name . 'time'] = time();

        return Url::to([$this->id, 'token' => $token]);
    }

    public function getPdfData($token) {
        $session = Yii::$app->getSession();
        $session->open();
        $name = $this->getSessionKey();

        if ($session[$name . 'token'] === null || $session[$name . 'token'] != $token) {
            $this->errorMessage = 'token不存在';
            return FALSE;
        }

        if ($this->expire !== 0 && time() - $session[$name . 'time'] > $this->expire) {
            $this->errorMessage = 'token已过期';
            return FALSE;
        }

        if (!($data = $session[$name . 'data'])) {
            $this->errorMessage = 'token不存在';
            return FALSE;
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function run() {
        $token = Yii::$app->request->getQueryParam(self::TOKEN_GET_VAR);
        $orderData = $this->getPdfData($token);

        if (!$orderData) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'error' => 1,
                'message' => $this->errorMessage,
            ];
        } else {
            Yii::$app->response->format = Response::FORMAT_RAW;
            $this->setHttpHeaders();
            return static::renderPdf($orderData);
        }
    }

    public function getErrorMessage() {
        return $this->errorMessage;
    }

    public static function renderPdf($data) {
        return serialize($data);
    }

    /**
     * Returns the session variable name used to store verification code.
     * @return string the session variable name
     */
    protected function getSessionKey() {
        return '__pdf/' . $this->getUniqueId();
    }

    protected function generateToken($data) {
        $token = md5(serialize($data).(string)time());
        return $token;
    }

     /**
     * Sets the HTTP headers needed by image response.
     */
    protected function setHttpHeaders() {
        Yii::$app->getResponse()->getHeaders()
            ->set('Pragma', 'public')
            ->set('Expires', '0')
            ->set('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
            ->set('Content-Transfer-Encoding', 'binary')
            ->set('Content-type', 'application/pdf')
            ->set('Content-Disposition', 'attachment; filename='.'ss.pdf');
    }
}
