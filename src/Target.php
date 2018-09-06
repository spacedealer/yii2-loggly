<?php
/**
 * Target.php file.
 *
 * Post log messages to [Loggly](http://loggly.com/) with this log target class.
 * Loggly is a cloud based log management service:
 *
 * - https://www.loggly.com/
 *
 * This is based on the yii-loggly extension by Alexey Ashurok:
 *
 * - http://github.com/aotd1/yii-loggly
 *
 * @author Dirk Adler <adler@spacedealer.de>
 * @copyright Copyright &copy; 2008-2014 spacedealer GmbH
 */

namespace spacedealer\loggly;

use yii\base\InvalidConfigException;
use yii\log\Logger;

/**
 * Class Target
 *
 * @package spacedealer\loggly
 */
class Target extends \yii\log\Target
{
    /**
     * @var string loggly customer token
     */
    public $customerToken;

    /**
     * @var bool
     */
    public $finishRequest = true;

    /**
     * @var string
     */
    public $baseUrl = 'https://logs-01.loggly.com';

    /**
     * @var string Path to cert file. If not set bundled cert.pem is used.
     * @see https://www.loggly.com/docs/rsyslog-tls-configuration/
     */
    public $cert;

    /**
     * @var bool whether ips are logged. disabled by default.
     */
    public $enableIp = false;

    /**
     * @var bool whether trail id is logged. disabled by default.
     */
    public $enableTrail = false;
    
    /**
     * @var boolean whether trace is logged. disabled by default.
     */
    public $enableTrace = false;

    /**
     * @var string md5 based random id. will be generated if not set.
     */
    public $trail;

    /**
     * @var int maximal time the curl connection phase is allowed to take in seconds.
     */
    public $connectTimeout = 5;

    /**
     * @var int maximal time the curl request is allowed to take in seconds.
     */
    public $timeout = 5;

    /**
     * @var array optional list of tags
     * @see https://www.loggly.com/docs/tags/
     */
    public $tags = [];

    /**
     * @var bool Whether to use bulk upload of messages.
     */
    public $bulk = false;

    /**
     * @var resource cURL-Handle
     */
    private $_curl;

    /**
     * @var string log url including customer token and optional tags
     */
    private $_url;

    /**
     * Validate config and init.
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        // validate customer token
        if (!is_string($this->customerToken) || strlen($this->customerToken) !== 36) {
            throw new InvalidConfigException("Loggly customer token must be a valid 36 character string");
        }

        // init certificate
        if ($this->cert === null) {
            $this->cert = __DIR__ . '/cert.pem';
        }
        if (!file_exists($this->cert)) {
            throw new InvalidConfigException("Certificate file '{$this->cert}' not found.");
        }

        // init trail id
        if (empty($this->trail)) {
            $this->trail = md5(rand() . rand() . rand() . rand());
        }

        // init endpoint url
        $endpoint = ($this->bulk === true) ? '/bulk/' : '/inputs/';
        $tags = empty($this->tags) ? '' : '/tag/' . implode(',', $this->tags) . '/';
        $this->_url = $this->baseUrl . $endpoint . $this->customerToken . $tags;
    }

    /**
     * The loggly post url.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->_url;
    }

    /**
     * Push log [[messages]] to loggly.
     */
    public function export()
    {
        if ($this->finishRequest && function_exists('fastcgi_finish_request')) {
            session_write_close();
            fastcgi_finish_request();
        }

        $ch = $this->initCurl();

        // process messages
        if ($this->bulk === true) {
            $messages = [];
            foreach ($this->messages as $message) {
                $messages[] = json_encode($this->formatMessage($message), JSON_FORCE_OBJECT);
            }
            $data = implode("\n", $messages);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_exec($ch);
        } else {
            foreach ($this->messages as $message) {
                $data = json_encode($this->formatMessage($message), JSON_FORCE_OBJECT);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                curl_exec($ch);
            }
        }
    }

    /**
     * Compile log message. Adds remote ip address and trail id if enabled.
     *
     * @param array $message
     * @return array
     */
    public function formatMessage($message)
    {
        list($text, $level, $category, $timestamp, $traces) = $message;
        $level = Logger::getLevelName($level);
        $msg = [
            'timestamp' => date('Y/m/d H:i:s', $timestamp),
            'level' => $level,
            'category' => $category,
            'message' => $text,
        ];
        if ($this->enableIp) {
            $msg['ip'] = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
        }
        if ($this->enableTrail) {
            $msg['trail'] = $this->trail;
        }
        if ($this->enableTrace) {
            /*
             * since loggly has some issues with nested json:
             * format array with minimal nesting
             */
            $toLog = [];
            foreach ($traces as $trace) {
                if (empty($trace['file'])) {
                    continue;
                }
                $toLog[] = "{$trace['file']}({$trace['line']})";
            }
            $msg['trace'] = $toLog;
        }

        return $msg;
    }

    /**
     * Init curl.
     *
     * @return resource
     */
    private function initCurl()
    {
        if ($this->_curl !== null) {
            return $this->_curl;
        }

        $this->_curl = curl_init();
        curl_setopt($this->_curl, CURLOPT_URL, $this->getUrl());
        curl_setopt($this->_curl, CURLOPT_HTTPHEADER, ['Content-type: application/json']);
        curl_setopt($this->_curl, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        curl_setopt($this->_curl, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($this->_curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->_curl, CURLOPT_POST, 1);
        curl_setopt($this->_curl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($this->_curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($this->_curl, CURLOPT_CAINFO, $this->cert);

        return $this->_curl;
    }

    /**
     * Closes open curl connection.
     */
    public function __destruct()
    {
        if ($this->_curl !== null) {
            curl_close($this->_curl);
        }
    }
} 
