<?php
/**
 * LogglyTarget.php file.
 *
 * This is based on the yii-loggly extension by Alexey Ashurok.
 *
 * @author Alexey Ashurok <work@aotd.ru>
 * @author Dirk Adler <adler@spacedealer.de>
 * @link http://www.spacedealer.de
 * @link http://github.com/aotd1/yii-loggly
 * @link http://loggly.com/
 * @copyright Copyright &copy; 2008-2014 spacedealer GmbH
 */


namespace spacedealer\loggly;

use yii\base\InvalidConfigException;
use yii\log\Logger;
use yii\log\Target;

/**
 * Class LogglyTarget
 *
 * @package spacedealer\loggly
 */
class LogglyTarget extends Target
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
	public $url = 'https://logs-01.loggly.com/inputs/';

	/* @var string */
	public $cert;

	/**
	 * @var bool whether ips are logged. disabled by default.
	 */
	public $enableIp = false;

	/**
	 * @var array optional list of tags
	 * @see https://www.loggly.com/docs/tags/
	 */
	public $tags = array();

	/**
	 * @var resource cURL-Handle
	 */
	private $_curl;

	private $_tagsUrl = '';

	public function init()
	{
		if (!is_string($this->customerToken) || strlen($this->customerToken) !== 36) {
			throw new InvalidConfigException("Loggly customer token must be a valid 36 character string");
		}
		if ($this->cert === null) {
			$this->cert = __DIR__ . '/cert.pem';
		}
		if (!empty($this->tags)) {
			$this->_tagsUrl = '/tag/' . implode(',', $this->tags) . '/';
		}
	}

	/**
	 * Push log [[messages]] to Loggly.
	 */
	public function export()
	{
		if ($this->finishRequest && function_exists('fastcgi_finish_request')) {
			session_write_close();
			fastcgi_finish_request();
		}

		$ch = $this->initCurl();

		// process messages
		foreach ($this->messages as $message) {
			$data = json_encode($this->formatMessage($message), JSON_FORCE_OBJECT);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_exec($ch);
		}
	}

	/**
	 * @param array $message
	 * @return array
	 */
	public function formatMessage($message)
	{
		list($text, $level, $category, $timestamp) = $message;
		$level = Logger::getLevelName($level);
		$msg = [
			'timestamp' => date('Y/m/d H:i:s', $timestamp),
			'level' => $level,
			'category' => $category,
			'message' => $text,
		];
		if ($this->enableIp) {
			$msg['ip'] = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
		}
		return $msg;
	}

	/**
	 * @return resource
	 */
	private function initCurl()
	{
		if ($this->_curl !== null) {
			return $this->_curl;
		}

		$this->curl = curl_init();
		curl_setopt($this->_curl, CURLOPT_URL, $this->url . $this->customerToken . $this->tagsUrl);
		curl_setopt($this->_curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
		curl_setopt($this->_curl, CURLOPT_TIMEOUT, 10);
		curl_setopt($this->_curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->_curl, CURLOPT_POST, 1);
		curl_setopt($this->_curl, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($this->_curl, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($this->_curl, CURLOPT_CAINFO, $this->cert);

		return $this->_curl;
	}

	public function __destruct()
	{
		if ($this->_curl !== null) {
			curl_close($this->_curl);
		}
	}
} 