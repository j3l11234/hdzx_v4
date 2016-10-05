<?php
namespace frontend\actions;

use Yii;
use yii\base\Action;
use yii\helpers\Url;
use yii\web\Response;
use TCPDF;

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
        $content = Yii::$app->getView()->render('/apply_doc', ['order' => $data], null);

        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('学生活动服务中心');
        $pdf->SetTitle('学生活动服务中心场地申请审批表');

        // remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetMargins(PDF_MARGIN_LEFT, 10, PDF_MARGIN_RIGHT);
        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set some language-dependent strings (optional)
        if (@file_exists(dirname(__FILE__).'/lang/chi.php')) {
            require_once(dirname(__FILE__).'/lang/chi.php');
            $pdf->setLanguageArray($l);
        }

        // ---------------------------------------------------------

        // set font
        $pdf->SetFont('stsongstdlight', '', 20);

        $pdf->setListIndentWidth(10);

        // add a page
        $pdf->AddPage();

        $pdf->writeHTML($content, true, false, true, false, '');
        $pdf->lastPage();
        // ---------------------------------------------------------

        //Close and output PDF document
        $outputData = $pdf->Output('example_002.pdf', 'S');


        return $outputData;
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
            ->set('Content-type', 'application/pdf')
            ->set('Cache-Control', 'Public')
            ->set('Pragma', 'public')
            ->set('Expires', gmdate('D, d M Y H:i:s', time()+20*86400).' GMT')
            ->set('Content-Disposition', 'attachment; filename='.'学生活动服务中心场地申请审批表.pdf');
    }
}
