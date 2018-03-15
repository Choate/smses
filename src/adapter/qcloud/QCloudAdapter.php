<?php
/**
 * Created by PhpStorm.
 * User: Choate
 * Date: 2018/3/12
 * Time: 16:54
 */

namespace choate\smses\adapter\qcloud;

use choate\smses\AdapterInterface;
use choate\smses\SendException;
use Qcloud\Sms\SmsMultiSender;
use Qcloud\Sms\SmsSingleSender;


class QCloudAdapter implements AdapterInterface
{
    /**
     * @var array
     */
    private $templateItems;

    /**
     * @var bool
     */
    private $enableTemplate;

    /**
     * @var string
     */
    private $secretId;

    /**
     * @var string
     */
    private $secretKey;

    /**
     * @var string
     */
    private $signature = '';

    /**
     * @var int
     */
    private $type = 0;


    /**
     * QCLoudAdapter constructor.
     * @param string $secretId
     * @param string $secretKey
     */
    public function __construct($secretId, $secretKey)
    {
        $this->secretId = $secretId;
        $this->secretKey = $secretKey;
    }

    public function send($mobile, $content, $region = '86')
    {
        $sender = new SmsSingleSender($this->secretId, $this->secretKey);
        $template = $this->generateTemplate($content);
        if (is_null($template)) {
            $responseData = $sender->send($this->getType(), $region, $mobile, $this->appendSignature($content), '', '');
        } else {
            $responseData = $sender->sendWithParam($region, $mobile, $template->id, $template->params, $this->signature);
        }

        return $this->response($responseData);
    }

    public function batchSend(array $mobiles, $content, $region = '86')
    {
        $sender = new SmsMultiSender($this->secretId, $this->secretKey);

        return $sender->send($this->getType(), $region, $mobiles, $this->appendSignature($content), '', '');
    }

    public function getName() {
        return 'QCloud';
    }

    /**
     * @param $content
     * @return null|\StdClass
     */
    protected function generateTemplate($content)
    {
        $templateItems = $this->getTemplateItems();
        if (!$this->isEnableTemplate()) {
            return null;
        }
        foreach ($templateItems as $id => $pattern) {
            $targetParams = [];
            if (is_array($pattern)) {
                $pattern = isset($pattern['pattern']) ? $pattern['pattern'] : null;
                $targetParams = $pattern['params'];
            }
            if (preg_match($pattern, $content, $matchItems)) {
                $matchItems = array_slice($matchItems, 1);
                $params = $matchItems;
                if ($targetParams) {
                    foreach ($targetParams as $key => $param) {
                        $params[$param] = isset($matchItems[$key]) ? $matchItems[$key] : null;
                    }
                }
                $template = new \StdClass;
                $template->id = $id;
                $template->params = $params;

                return $template;
            }
        }

        return null;
    }

    protected function response($response)
    {
        $result = json_decode($response);
        $valid = strcmp($result->result, '0') === 0 ;
        if ($valid) {
            return $valid;
        } else {
            throw new SendException($result->errmsg);
        }
    }

    protected function appendSignature($content)
    {
        $signature = $this->getSignature();

        if ($signature) {
            return $signature . $content;
        }

        return $content;
    }


    /**
     * @return array
     */
    public function getTemplateItems()
    {
        return $this->templateItems;
    }

    /**
     * @param array $templateItems
     */
    public function setTemplateItems($templateItems)
    {
        $this->templateItems = $templateItems;
    }

    /**
     * @return bool
     */
    public function isEnableTemplate()
    {
        return $this->enableTemplate;
    }

    /**
     * @param bool $enableTemplate
     */
    public function setEnableTemplate($enableTemplate)
    {
        $this->enableTemplate = $enableTemplate;
    }

    /**
     * @return mixed
     */
    public function getSignature()
    {
        return $this->signature;
    }

    /**
     * @param mixed $signature
     */
    public function setSignature($signature)
    {
        $this->signature = $signature;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }
}