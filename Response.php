<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 01.10.2017
 * Time: 12:17
 */

// declare(strict_types=1);

namespace cheremhovo\smsTraffic;

use yii\base\Component;

/**
 * Class Response
 * @package cheremhovo\smsTraffic
 */
class Response extends Component
{
    /**
     * @var \yii\httpclient\Response
     */
    private $response;

    /**
     * Response constructor.
     * @param \yii\httpclient\Response $response
     * @param array $config
     */
    public function __construct(
        \yii\httpclient\Response $response,
        array $config = []
    )
    {
        parent::__construct($config);
        $this->response = $response;
    }

    /**
     * @return \SimpleXMLElement
     */
    public function createSimpleXml()
    {
        $xml = simplexml_load_string($this->response->content);
        return $xml;
    }

    /**
     * @return bool
     */
    public function isOk()
    {
        if (!$this->response->isOk) {
            return false;
        }
        $xml = $this->createSimpleXml();
        if (strtolower($xml->result) !== 'ok') {
            return false;
        }
        return true;
    }
}