<?php

declare(strict_types = 1);
namespace Xutengx\Request\Traits;

use BadMethodCallException;
use Closure;
use InvalidArgumentException;
use Xutengx\Request\Exception\{IllegalArgumentException, NotFoundArgumentException};

trait Filter {

	/**
	 * 过滤表达式
	 * @var array
	 */
	protected $filterRules = [
		'email'     => '/^[\w-]+(\.[\w-]+)*@[\w-]+(\.[\w-]+)+$/',
		'url'       => '/\b(([\w-]+:\/\/?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|\/)))/',
		// 可以正负数字
		'int'       => '/^-?\d+$/',
		// 密码(允许5-32字节，允许字母数字下划线)
		'password'  => '/^[\w]{5,32}$/',
		// 帐号(字母开头，允许5-16字节，允许字母数字下划线)
		'account'   => '/^[a-zA-Z][a-zA-Z0-9_]{5,16}$/',
		// 身份证：中国的身份证为15位或18位
		'id_number' => '/^\d{15}|\d{18}$/',
		// 中国邮政编码
		'mail'      => '/^[1-9]\d{5}(?!\d)$/',
		// 腾讯QQ号 腾讯QQ号从10000开始
		'qq'        => '/^[1-9][0-9]{4,}$/',
		// 国内电话号码 匹配形式如 0511-4405222 或 021-87888822
		'telephone' => '/^\d{3}-\d{8}|\d{4}-\d{7}$/',
		// 中国手机号码：(86)*0*13\d{9}
		'tel'       => '/^1[3|4|5|7|8][0-9]\d{8}$/',
		// 大小写字母,数字,下划线
		'string'    => '/^\w+$/',
		// 大小写字母,数字,下划线,减号'-'
		'token'     => '/^[\w-]+$/',
		// 大小写字母,数字,下划线 32位
		'sign'      => '/^[\w]{32}$/',
		// 2-8位
		'name'      => '/^[_\w\d\x{4e00}-\x{9fa5}]{2,8}$/iu'
	];

	/**
	 * 增加一个验证规则
	 * @param string $ruleName
	 * @param string|Closure $rule
	 * @return bool
	 */
	public function addRule(string $ruleName, $rule): bool {
		if (isset($this->filterRules[$ruleName]))
			throw new InvalidArgumentException("Rule [$ruleName] is already exist.");
		if (is_string($rule)) {
			$this->filterRules[$ruleName] = $rule;
			return true;
		}
		elseif ($rule instanceof Closure) {
			$this->filterRules[$ruleName] = function(string $string) use ($rule): bool {
				return $rule($string);
			};
			return true;
		}
		throw new InvalidArgumentException("The rule mast instanceof string or Closure.");
	}

	/**
	 * 判断是否存在一个验证规则
	 * @param string $ruleName
	 * @return bool
	 */
	public function hasRule(string $ruleName): bool {
		return isset($this->filterRules[$ruleName]);
	}

	/**
	 * 移除一个验证规则
	 * @param string $ruleName
	 * @return bool
	 */
	public function delRule(string $ruleName): bool {
		if (!$this->hasRule($ruleName))
			throw new InvalidArgumentException("Rule [$ruleName] is not exist.");
		unset($this->filterRules[$ruleName]);
		return !$this->hasRule($ruleName);
	}

	/**
	 * 更新一个验证规则
	 * @param string $ruleName
	 * @param string|Closure $rule
	 * @return bool
	 */
	public function editRule(string $ruleName, $rule): bool {
		return $this->delRule($ruleName) && $this->addRule($ruleName, $rule);
	}

	/**
	 * 一个请求参数是否存在
	 * 依次检测请求体, url, 域名, file
	 * @param string $key
	 * @return bool
	 */
	public function has(string $key): bool {
		return isset($this->{$this->method}[$key]) || isset($this->get[$key]) || isset($this->domain[$key]) ||
		       $this->file->has($key);
	}

	/**
	 * 获取全部参数
	 * 注:当存在相同键名参数时, 将会发生覆盖
	 * @return array
	 */
	public function all(): array {
		return array_merge($this->{$this->method}, $this->get, $this->domain, $this->file->get());
	}

	/**
	 * 获取当前请求类型的参数
	 * @param string $key
	 * @param string $ruleName 预定规则 or 正则表达式
	 * @return mixed
	 * @throws NotFoundArgumentException 参数不存在时抛出
	 * @throws IllegalArgumentException 验证不通过时抛出
	 */
	public function input(string $key, string $ruleName = null) {
		return $this->filterFunction($this->method, $key, $ruleName);
	}

	/**
	 * 获取指定请求类型的参数
	 * @param string $function
	 * @param array $parameters
	 * @return mixed
	 * @throws IllegalArgumentException
	 * @throws NotFoundArgumentException
	 */
	public function __call(string $function, array $parameters = []) {
		if (in_array($function, [
			'domain',
			'get',
			'post',
			'put',
			'delete',
			'head',
			'option',
			'patch',
			'cookie',
			'file'
		], true)) {
			return $this->filterFunction($function, ...$parameters);
		}
		else throw new BadMethodCallException('Call to undefined method ' . static::class . '::' . $function . '()');
	}

	/**
	 * 过滤请求参数
	 * @param string $method
	 * @param string $key 为null时返回所有
	 * @param string $ruleName 为null时不验证
	 * @return mixed
	 * @throws NotFoundArgumentException 参数不存在时抛出
	 * @throws IllegalArgumentException 验证不通过时抛出
	 */
	protected function filterFunction(string $method, string $key = null, string $ruleName = null) {
		if (is_null($key))
			return $this->{$method};
		elseif (isset($this->$method[$key])) {
			if (is_null($ruleName) || $this->filterMatch($this->$method[$key], $ruleName))
				return $this->$method[$key];
			else throw new IllegalArgumentException("Invalid request argument [$key] with rule [$ruleName].");
		}
		else throw new NotFoundArgumentException("Not found request argument [$key] in method [$method].");
	}

	/**
	 * 正则匹配
	 * @param string $str 检验对象
	 * @param string $rule 匹配规则
	 * @return bool
	 */
	protected function filterMatch(string $str, string $rule): bool {
		$rule = $this->filterRules[$rule] ?? $rule;
		if ($rule instanceof Closure) {
			return $rule($str) ? true : false;
		}
		return preg_match($rule, $str) ? true : false;
	}

}
