<?php

declare(strict_types = 1);
namespace Xutengx\Request;

use InvalidArgumentException;
use Xutengx\Request\Component\UploadFile;
use Xutengx\Request\Traits\{Filter, RequestInfo};
use Xutengx\Tool\Tool;

class Request {

	use RequestInfo, Filter;

	protected $domain  = [];
	protected $get     = [];
	protected $post    = [];
	protected $put     = [];
	protected $delete  = [];
	protected $options = [];
	protected $head    = [];
	protected $patch   = [];
	protected $cookie  = [];
	protected $file;
	protected $tool;

	/**
	 * Request constructor.
	 * @param UploadFile $uploadFile
	 * @param Tool $tool
	 */
	public function __construct(UploadFile $uploadFile, Tool $tool) {
		$this->RequestInfoInit();
		$this->file = $uploadFile;
		$this->tool = $tool;
	}

	/**
	 * 过滤参数
	 * @param array $parameters
	 * @return array
	 */
	protected static function filter(array $parameters): array {
		return static::recursiveAddslashes(static::recursiveHtmlspecialchars($parameters));
	}

	/**
	 * 预定义的字符转换为 HTML 实体, 预定义的字符是：& （和号）, " （双引号）, ' （单引号）,> （大于）,< （小于）
	 * @param array $arr
	 * @return array
	 */
	protected static function recursiveHtmlspecialchars(array $arr): array {
		$filtered = [];
		foreach ($arr as $k => $v)
			$filtered[$k] = is_string($v) ? htmlspecialchars($v) : static::recursiveHtmlspecialchars($v);
		return $filtered;
	}

	/**
	 * 在预定义字符之前添加反斜杠, 预定义字符是：单引号（'）,双引号（"）, 反斜杠（\）, NULL
	 * 默认地，PHP 对所有的 GET、POST 和 COOKIE 数据自动运行 addslashes()。
	 * 所以您不应对已转义过的字符串使用 addslashes()，因为这样会导致双层转义。
	 * 遇到这种情况时可以使用函数 get_magic_quotes_gpc() 进行检测
	 * @param array $parameters
	 * @return array
	 */
	protected static function recursiveAddslashes(array $parameters): array {
		$filtered = [];
		foreach ($parameters as $k => $v)
			$filtered[addslashes($k)] = is_string($v) ? addslashes($v) : static::recursiveAddslashes($v);
		return $filtered;
	}

	/**
	 * 参数过滤及设置
	 * @param array $domainParameters 路由的`域名`参数
	 * @param array $staticParameters url静态参数(pathInfo参数)
	 * @return Request
	 * @throws Exception\UploadFileException
	 * @throws \Xutengx\Tool\Exception\DecodeXMLException
	 */
	public function setParameters(array $domainParameters = [], array $staticParameters = []): Request {
		$this->{$this->method} = $this->parsingData();
		$this->get             = array_merge($this->filter($staticParameters), $this->recursiveHtmlspecialchars($_GET));
		$this->cookie          = $this->recursiveHtmlspecialchars($_COOKIE);
		$this->post            = $this->recursiveHtmlspecialchars($_POST);
		$this->domain          = $this->filter($domainParameters);
		$this->file->addFiles($_FILES);
	}

	/**
	 * 设置cookie, 即时生效
	 * @param string $name
	 * @param array|string $value
	 * @param int $expire
	 * @param string $path
	 * @param string $domain
	 * @param bool $secure
	 * @param bool $httpOnly
	 * @return void
	 */
	public function setCookie(string $name, $value = '', int $expire = 0, string $path = '', string $domain = '',
		bool $secure = false, bool $httpOnly = true): void {
		if (!is_string($value) && !is_array($value)) {
			throw new InvalidArgumentException();
		}
		$expire              += time();
		$this->cookie[$name] = $_COOKIE[$name] = $value;
		if (is_array($value))
			foreach ($value as $k => $v)
				setcookie($name . '[' . $k . ']', $v, $expire, $path, $domain, $secure, $httpOnly);
		else
			setcookie($name, $value, $expire, $path, $domain, $secure, $httpOnly);
	}

	/**
	 * 解析特定类型的数据
	 * @return array
	 * @throws \Xutengx\Tool\Exception\DecodeXMLException
	 */
	protected function parsingData(): array {
		// 未被php解析的内容
		$temp = file_get_contents('php://input');

		// 未被php解析的内容类型为`查询字符串`
		if (stripos($this->contentType, 'application/x-www-form-urlencoded') !== false) {
			parse_str($temp, $tempArr);
			return $this->filter($tempArr);
		}
		// 未被php解析的内容类型为`json`
		elseif (stripos($this->contentType, 'application/json') !== false) {
			return json_decode($temp, true);
		}
		// 未被php解析的内容类型为`xml`
		elseif (stripos($this->contentType, 'application/xml') !== false) {
			return $this->tool->xmlDecode($temp);
		}
		// 未被php解析的内容类型为`流`
		else
			return $this->filter($this->getStream($temp));
	}

	/**
	 * 分析`stream`获得数据, 兼容文件上传
	 * @param string $input
	 * @return array
	 */
	protected function getStream(string $input): array {
		// 最终返回
		$requestData = [];

		// 获取内容分解符
		preg_match('/boundary=(.*)$/', $this->contentType, $boundaryMatches);

		// 内容分解符为空, 则内容类型为规则形式编码
		if (empty($boundaryMatches)) {
			// 解析查询字符串
			$this->analysisParameterString($input, $requestData);
			return $requestData;
		}

		// 用边界拆分并去掉最后一个元素
		$allBlocks = $this->splitInput($boundaryMatches[1], $input);

		// 循环解析每个部分
		// 区别对待文件与字符
		foreach ($allBlocks as $block)
			(strpos($block['header'], 'filename="') !== false) ? $this->analysisFileBlock($block) :
				$this->analysisParameterBlock($block, $requestData);
		return $requestData;
	}

	/**
	 * 拆分输入块
	 * @param string $delimiter
	 * @param string $input
	 * @return array
	 */
	protected function splitInput(string $delimiter, string $input): array {
		$allBlocks = explode('--' . $delimiter . "\r\n", $input);
		// 移除头部无效元素
		array_shift($allBlocks);
		// 移除末尾无效元素
		array_pop($allBlocks);
		// 格式化
		foreach ($allBlocks as $k => $block) {
			$tempBlocks = explode("\r\n\r\n", $block);
			$temp       = [];
			for ($i = 1; $i < count($tempBlocks); $i++) {
				$temp[] = $allBlocks[$k][$i];
			}
			$allBlocks[$k]['header'] = $tempBlocks[0];
			$allBlocks[$k]['body']   = rtrim(implode(" ", $temp), "\r\n");
		}
		return $allBlocks;
	}

	/**
	 * 解析规则形式编码
	 * @param string $input
	 * @param array &$requestData
	 * @return void
	 */
	protected function analysisParameterString(string $input, array &$requestData): void {
		parse_str(urldecode($input), $requestData);
	}

	/**
	 * 解析steam文件类型
	 * @param array $inputBlock
	 * @return void
	 */
	protected function analysisFileBlock(array $inputBlock): void {
		preg_match("/name=\"([^\"]*)\".*filename=\"([^\"].*?)\".*Content-Type:\s+(.*?)$/s", $inputBlock['header'],
			$matches);
		if (!empty($matches)) {
			// 加入文件对象
			$this->file->addFile([
				'key_name' => $matches[1],
				'name'     => $matches[2],
				'type'     => $matches[3],
				'size'     => strlen($inputBlock['body']),
				'content'  => $inputBlock['body']
			]);
		}
	}

	/**
	 * 解析steam参数类型
	 * @param array $inputBlock
	 * @param array &$requestData
	 * @return void
	 */
	protected function analysisParameterBlock(array $inputBlock, array &$requestData): void {
		preg_match('/name=\"([^\"]*)\"$/s', $inputBlock['header'], $matches);
		$requestData[$matches[1]] = $inputBlock['body'];
	}

}
