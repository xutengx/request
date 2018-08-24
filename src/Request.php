<?php

declare(strict_types = 1);
namespace Xutengx\Request;

use Xutengx\Request\Component\UploadFile;
use Xutengx\Request\Traits\{Filter, RequestInfo};

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
	protected $input   = [];
	protected $cookie  = [];
	protected $file;

	/**
	 * Request constructor.
	 * @param UploadFile $uploadFile
	 */
	public function __construct(UploadFile $uploadFile) {
		$this->RequestInfoInit();
		$this->file = $uploadFile;
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
	 * 参数获取
	 * @param array $domainParameters 路由的`域名`参数
	 * @param array $staticParameters url静态参数(pathInfo参数)
	 * @return Request
	 * @throws Exception\UploadFileException
	 */
	public function setParameters(array $domainParameters = [], array $staticParameters = []): Request {
		$this->get    = $this->filter($staticParameters);
		$this->domain = $this->filter($domainParameters);
		return $this->setRequestParameters();
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
		$expire              += time();
		$this->cookie[$name] = $_COOKIE[$name] = $value;
		if (is_array($value))
			foreach ($value as $k => $v)
				setcookie($name . '[' . $k . ']', $v, $expire, $path, $domain, $secure, $httpOnly);
		else
			setcookie($name, $value, $expire, $path, $domain, $secure, $httpOnly);
	}

	/**
	 * 获取参数到当前类的属性
	 * @return Request
	 * @throws Exception\UploadFileException
	 */
	protected function setRequestParameters(): Request {
		$this->cookie = $this->recursiveHtmlspecialchars($_COOKIE);

		if (($argc = $this->method) !== 'get') {
			$temp         = file_get_contents('php://input');
			$content_type = $this->contentType;

			if (stripos($content_type, 'application/x-www-form-urlencoded') !== false) {
				parse_str($temp, $this->{$argc});
				$this->{$argc} = $this->filter($this->{$argc});
			}
			elseif (stripos($content_type, 'application/json') !== false) {
				$this->{$argc} = json_decode($temp, true);
			}
			elseif (stripos($content_type, 'application/xml') !== false) {
				$this->{$argc} = obj(Tool::class)->xml_decode($temp);
			}
			else {
				$this->{$argc} = !empty($_POST) ? $this->recursiveHtmlspecialchars($_POST) :
					$this->filter($this->getStream($temp));
			}
		}
		$this->get = array_merge($this->get, $this->recursiveHtmlspecialchars($_GET));
		$this->consistentFile();
		$this->input = $this->{$argc};
		return $this;
	}

	/**
	 * 分析stream获得数据, put文件上传时,php不会帮忙解析信息,只有手动了
	 * @param string $input
	 * @return array
	 */
	protected function getStream(string $input): array {
		$requestData = [];
		// grab multipart boundary from content type header
		preg_match('/boundary=(.*)$/', $this->contentType, $matches);

		// content type is probably regular form-encoded
		if (!count($matches)) {
			// we expect regular puts to containt a query string containing data
			parse_str(urldecode($input), $requestData);
			return $requestData;
		}

		// split content by boundary and get rid of last -- element
		$allBlocks = preg_split("/-+$matches[1]/", $input);
		array_pop($allBlocks);

		// loop data blocks
		foreach ($allBlocks as $block) {
			if (empty($block))
				continue;
			// you'll have to var_dump $block to understand this and maybe replace \n or \r with a visibile char
			// parse uploaded files
			if (strpos($block, 'filename=') !== false) {
				// match "name", then everything after "stream" (optional) except for prepending newlines
				preg_match("/name=\"([^\"]*)\".*filename=\"([^\"].*?)\".*Content-Type:\s+(.*?)[\n|\r|\r\n]+([^\n\r].*)?$/s",
					$block, $matches);
				// 兼容无文件上传的情况
				if (empty($matches))
					continue;
				$content_blob = $matches[4];
				$content_blob = substr($content_blob, 0, strlen($content_blob) - strlen(PHP_EOL) * 2);  // 移除尾部多余换行符
				$this->file->addFile([
					'key_name' => $matches[1],
					'name'     => $matches[2],
					'type'     => $matches[3],
					'size'     => strlen($content_blob),
					'content'  => $content_blob
				]);
			}
			// parse all other fields
			else {
				// match "name" and optional value in between newline sequences
				preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $block, $matches);
				$requestData[$matches[1]] = $matches[2] ?? '';
			}
		}
		return $requestData;
	}

	/**
	 * 将$_FILES 放入 $this->file
	 * @return void
	 * @throws Exception\UploadFileException
	 */
	protected function consistentFile(): void {
		if (!empty($_FILES)) {
			$this->file->addFiles($_FILES);
		}
	}

}
