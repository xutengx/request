<?php

declare(strict_types = 1);
namespace Xutengx\Request\Component;

use Closure;
use InvalidArgumentException;
use Xutengx\Request\Exception\{IllegalArgumentException};

class Validator {
	/**
	 * @var array
	 */
	protected static $ruleReasonMap = [];
	/**
	 * 字段值所属类型 eg: string,numeric,array,file
	 * @var string
	 */
	public $type;
	/**
	 * 是否空值
	 * @var bool
	 */
	public $isEmpty = false;
	/**
	 * 字段name
	 * @var string
	 */
	public $name;
	/**
	 * 字段值引用
	 * @var mixed
	 */
	public $value;
	/**
	 * 已经验证过的数据
	 * @var array
	 */
	public $okData;

	/**
	 * 初始化键和值
	 * @param string $name
	 * @param mixed $value
	 * @param array $okData 已经验证过的数据,在做联动验证时会使用到
	 * @return Validator
	 * @throws IllegalArgumentException
	 */
	public function init(string $name, &$value, array &$okData = []): Validator {
		$this->name   = $name;
		$this->value  = $value;
		$this->okData = $okData;
		$this->getType();
		return $this;
	}

	/**
	 * 使用既有规则验证
	 * @param string $ruleString eg:accepted|require|in:2,3
	 * @return void
	 * @throws IllegalArgumentException
	 */
	public function useExistingRule(string $ruleString): void {
		// 拆分各个验证规则
		$ruleArr = explode('|', $ruleString);
		foreach ($ruleArr as $rule) {
			// 拆分规则与参数
			$ruleSpecific = explode(':', $rule);
			// 验证规则存在
			if (method_exists($this, $ruleFunctionName = array_shift($ruleSpecific))) {
				// 拆分参数
				$parameter = empty($ruleSpecific) ? [] : explode(',', implode(',', $ruleSpecific));
				// 验证
				if (!$this->$ruleFunctionName(...$parameter)) {
					$this->throwIllegalArgumentException($ruleFunctionName, $rule);
				}
			}
		}
	}

	/**
	 * 使用闭包规则验证
	 * @param Closure $closure
	 * @return void
	 * @throws IllegalArgumentException
	 */
	public function useClosureRule(Closure $closure): void {
		$message = null;
		if (!$closure($this, $message)) {
			$this->throwIllegalArgumentException('Closure', 'Closure', $message);
		}
	}

	/**
	 * 验证字段的值必须是 yes、on、1 或 true
	 * 这在“同意服务协议”时很有用
	 * @return bool
	 */
	protected function accepted(): bool {
		return $this->isEmpty() || in_array(is_string($this->value) ? strtolower($this->value) : $this->value,
				['yes', 'on', '1', 1, true, 'true'], true);
	}

	/**
	 * 有效url
	 * @return bool
	 */
	protected function activeUrl(): bool {
		return $this->isEmpty() || checkdnsrr($this->value, 'ANY');
	}

	/**
	 * 验证字段必须是给定日期之后的一个值
	 * 日期将会通过 PHP 函数 strtotime 传递
	 * @param string $dateString
	 * @return bool
	 */
	protected function after(string $dateString): bool {
		return $this->isEmpty() || (strtotime($this->value) > strtotime($dateString));
	}

	/**
	 * 验证字段必须是大小写字母
	 * @return bool
	 */
	protected function alpha(): bool {
		return $this->isEmpty() || ctype_alpha($this->value);
	}

	/**
	 * 验证字段可以包含字母和数字，以及破折号和下划线。
	 * @return bool
	 */
	protected function alphaDash(): bool {
		return $this->isEmpty() || $this->regex('/^[[a-zA-Z0-9_\-]*$/');
	}

	/**
	 * 验证字段可以包含字母和数字。
	 * @return bool
	 */
	protected function alphaNum(): bool {
		return $this->isEmpty() || $this->regex('/^[[a-zA-Z0-9]*$/');
	}

	/**
	 * 验证字段必须是 PHP 数组。
	 * @return bool
	 */
	protected function array(): bool {
		return is_array($this->value);
	}

	/**
	 * 验证字段必须是给定日期之前的一个值
	 * 日期将会通过 PHP 函数 strtotime 传递
	 * @param string $dateString
	 * @return bool
	 */
	protected function before(string $dateString): bool {
		return $this->isEmpty() || (strtotime($this->value) < strtotime($dateString));
	}

	/**
	 * 验证字段大小在给定的最小值和最大值之间
	 * 字符串、数字、数组和文件都可以使用该规则
	 * 注:当待验证值为`数字字符串`时, 当做数字处理
	 * @param string $min
	 * @param string $max
	 * @param string $type 使用指定类型处理
	 * @return bool
	 */
	protected function between(string $min, string $max, string $type = null): bool {
		if ($this->isEmpty())
			return true;
		switch ($type ?? $this->type) {
			case 'numeric':
				return ((float)$this->value >= (float)$min) && ((float)$this->value <= (float)$max);
			case 'string':
				$len = strlen($this->value);
				return ($len >= (int)$min) && ($len <= (int)$max);
			case 'array':
				$len = count($this->value);
				return ($len >= (int)$min) && ($len <= (int)$max);
			case 'file':
				return ($this->value->isGreater((int)$min)) && ($this->value->isLess((int)$max));
			default :
				return false;
		}
	}

	/**
	 * 验证字段必须可以被转化为布尔值
	 * 接收 true, false, 1, 0, "1" 和 "0" 等输入
	 * @return bool
	 */
	protected function boolean(): bool {
		return $this->isEmpty() || in_array(is_string($this->value) ? strtolower($this->value) : $this->value,
				['yes', 'no', 'on', 'off', '1', '0', 1, 0, true, false, 'true', 'false'], true);
	}

	/**
	 * 验证字段必须是一个基于 PHP strtotime 函数的有效日期
	 * @return bool
	 */
	protected function date(): bool {
		return $this->isEmpty() || strtotime($this->value) !== false;
	}

	/**
	 * 验证字段必须等于给定日期，日期会被传递到 PHP strtotime 函数。
	 * @param string $date
	 * @return bool
	 */
	protected function dateEquals(string $date): bool {
		return $this->isEmpty() || ((($time = strtotime($date)) !== false) && $time === strtotime($this->value));
	}

	/**
	 * 验证字段必须匹配指定格式，可以使用 PHP 函数date 或 date_format 验证该字段。
	 * 不可包含特殊字段| eg:Y-m-d H:i:s
	 * @return bool
	 */
	protected function dateFormat(): bool {
		// 因为参数有:,等歧义的符号, 所以特殊处理, 但依然不兼容|
		$parameter = func_get_args();
		$format    = implode(':', $parameter);
		return $this->isEmpty() ||
		       (is_array($info = date_parse_from_format($format, $this->value)) && empty($info['errors']));
	}

	/**
	 * 验证字段数值长度必须介于最小值和最大值之间。
	 * @param string $min
	 * @param string $max
	 * @return bool
	 */
	protected function digitsBetween(string $min, string $max): bool {
		return $this->isEmpty() || (is_numeric($this->value) && ($length = strlen((string)$this->value) >= (int)$min) &&
		                            ($length <= (int)$max));
	}

	/**
	 * 验证字段必须是格式正确的电子邮件地址
	 * @return bool
	 */
	protected function email(): bool {
		return $this->isEmpty() || (filter_var($this->value, FILTER_VALIDATE_EMAIL) !== false);
	}

	/**
	 * 验证字段必须是上传成功的文件。
	 * @return bool
	 */
	protected function file(): bool {
		return $this->isEmpty() || ($this->type === 'file');
	}

	/**
	 * 验证字段必须 relationship (大于, 小于, 等于, 大于等于, 小于等于, 不等于) 给定 field 字段
	 * 这两个字段类型必须一致
	 * @param string $relationship
	 * @param string $field
	 * @param string $type
	 * @return bool
	 */
	protected function compareField(string $relationship, string $field, ?string $type = null): bool {
		if (!in_array($relationship, ['<', '>', '=', '==', '===', '>=', '<=', '!=', '<>'], true)) {
			throw new InvalidArgumentException();
		}
		if ($this->isEmpty())
			return true;
		$fieldValue = $this->okData[$field] ?? null;
		switch ($type ?? $this->type) {
			case 'bool':
				$res = ((int)$this->value <=> (int)$fieldValue);
				break;
			case 'numeric':
			case 'integer':
			case 'int':
				$res = ((float)$this->value <=> (float)$fieldValue);
				break;
			case 'string':
				$res = strlen($this->value) <=> strlen($fieldValue);
				break;
			case 'array':
				$res = count($this->value) <=> count($fieldValue);
				break;
			case 'file':
				$res = $this->value->size <=> $fieldValue->size;
				break;
			default :
				return false;
		}
		if (($res === 0 && in_array($relationship, ['=', '==', '===', '>=', '<='], true)) ||
		    ($res === 1 && in_array($relationship, ['>', '>=', '!=', '<>'], true)) ||
		    ($res === -1 && in_array($relationship, ['<', '<=', '!=', '<>'], true))) {
			return true;
		}
		else return false;
	}

	/**
	 * 验证字段必须 relationship (大于, 小于, 等于, 大于等于, 小于等于, 不等于) 给定值
	 * 这两个字段类型必须一致
	 * @param string $relationship
	 * @param string $comparingValue
	 * @param string $type
	 * @return bool
	 */
	protected function compare(string $relationship, string $comparingValue, ?string $type = null): bool {
		if (!in_array($relationship, ['<', '>', '=', '==', '===', '>=', '<=', '!=', '<>'], true)) {
			throw new InvalidArgumentException();
		}
		if ($this->isEmpty())
			return true;
		switch ($type ?? $this->type) {
			case 'bool':
				$res = ((int)$this->value <=> (int)$comparingValue);
				break;
			case 'numeric':
			case 'integer':
			case 'int':
				$res = ((float)$this->value <=> (float)$comparingValue);
				break;
			case 'string':
				$res = strlen($this->value) <=> (int)($comparingValue);
				break;
			case 'array':
				$res = count($this->value) <=> (int)($comparingValue);
				break;
			case 'file':
				$res = $this->value->size <=> (int)($comparingValue);
				break;
			default :
				return false;
		}
		if (($res === 0 && in_array($relationship, ['=', '==', '===', '>=', '<='], true)) ||
		    ($res === 1 && in_array($relationship, ['>', '>=', '!=', '<>'], true)) ||
		    ($res === -1 && in_array($relationship, ['<', '<=', '!=', '<>'], true))) {
			return true;
		}
		else return false;
	}

	/**
	 * 验证字段必须小于等于最大值
	 * @param string $comparingValue
	 * @param string $type
	 * @return bool
	 */
	protected function max(string $comparingValue, string $type = null): bool {
		return $this->compare('<=', $comparingValue, $type);
	}

	/**
	 * 验证字段必须大于等于最小值
	 * @param string $comparingValue
	 * @param string $type
	 * @return bool
	 */
	protected function min(string $comparingValue, string $type = null): bool {
		return $this->compare('>=', $comparingValue, $type);
	}

	/**
	 * 验证字段必须大于给定 field 字段
	 * @param string $field
	 * @param string $type
	 * @return bool
	 */
	protected function gt(string $field, string $type = null): bool {
		return $this->compareField('>', $field, $type);
	}

	/**
	 * 验证字段必须大于等于给定 field 字段
	 * @param string $field
	 * @param string $type
	 * @return bool
	 */
	protected function gte(string $field, string $type = null): bool {
		return $this->compareField('>=', $field, $type);
	}

	/**
	 * 验证字段必须小于给定 field 字段
	 * @param string $field
	 * @param string $type
	 * @return bool
	 */
	protected function lt(string $field, string $type = null): bool {
		return $this->compareField('<', $field, $type);
	}

	/**
	 * 验证字段必须小于等于给定 field 字段
	 * @param string $field
	 * @param string $type
	 * @return bool
	 */
	protected function lte(string $field, string $type = null): bool {
		return $this->compareField('<=', $field, $type);
	}

	/**
	 * 验证字段必须是一个和指定字段不同的值。
	 * 注:指定字段必须在此之前被验证
	 * @param string $field
	 * @param string $type
	 * @return bool
	 */
	protected function different(string $field, string $type = null): bool {
		return $this->compareField('!=', $field, $type);
	}

	/**
	 * 验证字段必须有一个匹配字段
	 * 注:匹配字段必须在此之前被验证
	 * @param string $field 匹配字段名
	 * @param string $type
	 * @return bool
	 */
	protected function confirmed(string $field, string $type = null): bool {
		return $this->compareField('=', $field, $type);
	}

	/**
	 * 验证字段必须是数字且长度为 value 指定的值。
	 * @param string $length
	 * @return bool
	 */
	protected function digits(string $length): bool {
		return $this->isEmpty() || (is_numeric($this->value) && (strlen((string)$this->value) === (int)$length));
	}

	/**
	 * 验证字段值必须在给定的列表中
	 * @return bool
	 */
	protected function in(): bool {
		$range = func_get_args();
		return $this->isEmpty() || in_array($this->value, $range);
	}

	/**
	 * 验证字段值必须不在给定的列表中
	 * @return bool
	 */
	protected function notIn(): bool {
		$range = func_get_args();
		return $this->isEmpty() || !in_array($this->value, $range);
	}

	/**
	 * 验证字段必须是正整数
	 * @return bool
	 */
	protected function integer(): bool {
		return $this->isEmpty() || ctype_digit($this->value);
	}

	/**
	 * 验证字段必须是IP地址
	 * @return bool
	 */
	protected function ip(): bool {
		return $this->isEmpty() || (filter_var($this->value, FILTER_VALIDATE_IP) !== false);
	}

	/**
	 * 验证字段必须是IPv4地址
	 * @return bool
	 */
	protected function ipv4(): bool {
		return $this->isEmpty() || ip2long($this->value) !== false;
	}

	/**
	 * 验证字段必须是IPv6地址
	 * @return bool
	 */
	protected function ipv6(): bool {
		return $this->isEmpty() || (!$this->ipv4() && (filter_var($this->value, FILTER_VALIDATE_IP) !== false));
	}

	/**
	 * 验证字段必须是有效的JSON字符串
	 * @return bool
	 */
	protected function json(): bool {
		return $this->isEmpty() || !is_null(json_decode($this->value));
	}

	/**
	 * 正则匹配
	 * @param string $rule 匹配规则
	 * @return bool
	 */
	protected function regex(string $rule): bool {
		return (is_string($this->value) && preg_match($rule, $this->value)) ? true : false;

	}

	/**
	 * 验证字段不能匹配给定正则表达式
	 * @param string $rule 匹配规则
	 * @return bool
	 */
	protected function notRegex(string $rule): bool {
		return (is_string($this->value) && preg_match($rule, $this->value)) ? false : true;

	}

	/**
	 * 检查必填项
	 * 以下情况字段值都为空
	 * 1.值为null
	 * 2.值是空字符串
	 * 3.值是空数组
	 * 4.值是上传文件但出错
	 * @return bool
	 */
	protected function required(): bool {
		return !$this->isEmpty();
	}

	/**
	 * 验证字段在 $field 等于指定值 $compareValue 时必须存在且不能为空
	 * @param string $field
	 * @param string $compareValue
	 * @return bool
	 */
	protected function requiredIf(string $field, string $compareValue): bool {
		return (($this->okData[$field] ?? null) == $compareValue) ? !$this->isEmpty() : true;
	}

	/**
	 * 除非 $field 字段等于 $compareValue，否则验证字段不能空
	 * @param string $field
	 * @param string $compareValue
	 * @return bool
	 */
	protected function requiredUnless(string $field, string $compareValue): bool {
		return (($this->okData[$field] ?? null) != $compareValue) ? !$this->isEmpty() : true;
	}

	/**
	 * 验证字段只有在任一其它指定字段存在的情况才是必须的
	 * @return bool
	 */
	protected function requiredWith(): bool {
		$fields = func_get_args();
		foreach ($fields as $field) {
			if (!empty($this->okData[$field])) {
				return !$this->isEmpty();
			}
		}
		return true;
	}

	/**
	 * 验证字段只有在所有指定字段存在的情况下才是必须的
	 * @return bool
	 */
	protected function requiredWithAll(): bool {
		$fields = func_get_args();
		$flag   = true;
		foreach ($fields as $field) {
			$flag = $flag && !empty($this->okData[$field]);
		}
		return $flag ? !$this->isEmpty() : true;
	}

	/**
	 * 验证字段只有当任一指定字段不存在的情况下才是必须的
	 * @return bool
	 */
	protected function requiredWithout(): bool {
		$fields = func_get_args();
		foreach ($fields as $field) {
			if (empty($this->okData[$field])) {
				return !$this->isEmpty();
			}
		}
		return true;
	}

	/**
	 * 验证字段只有当所有指定字段不存在的情况下才是必须的
	 * @return bool
	 */
	protected function requiredWithoutAll(): bool {
		$fields = func_get_args();
		$flag   = true;
		foreach ($fields as $field) {
			$flag = $flag && empty($this->okData[$field]);
		}
		return $flag ? !$this->isEmpty() : true;
	}

	/**
	 * 给定字段和验证字段必须匹配
	 * @param string $field
	 * @return bool
	 */
	protected function same(string $field): bool {
		return $this->isEmpty() || ($this->value === $this->okData[$field]);
	}

	/**
	 * 验证字段必须有和给定值相匹配的尺寸/大小，对字符串而言，字符数目；对数值而言，是定整型值；对数组而言，是数组长度
	 * @param string $comparingValue
	 * @param string $type
	 * @return bool
	 */
	protected function size(string $comparingValue, ?string $type = null): bool {
		return $this->compare('=', $comparingValue, $type);
	}

	/**
	 * 验证字段必须是字符串
	 * @return bool
	 */
	protected function string(): bool {
		return $this->isEmpty() || is_string($this->value);
	}

	/**
	 * 验证字符必须是基于 PHP 函数 timezone_identifiers_list 的有效时区标识
	 * @return bool
	 */
	protected function timezone(): bool {
		return $this->isEmpty() || in_array($this->value, timezone_identifiers_list(), true);
	}

	/**
	 * 验证字段必须是数值
	 * @return bool
	 */
	protected function numeric(): bool {
		return $this->isEmpty() || is_numeric($this->value);
	}

	/**
	 * 验证字段必须是有效的 URL
	 * 以 http:// 或者 https:// 开头
	 * @return bool
	 */
	protected function url(): bool {
		return $this->isEmpty() || (filter_var($this->value, FILTER_VALIDATE_URL) !== false);
	}

	/**
	 * 是否为空
	 * @return bool
	 */
	protected function isEmpty(): bool {
		return $this->isEmpty;
	}

	/**
	 * 分析当前数据类型
	 * 赋值$this->isEmpty和$this->type
	 * @throws IllegalArgumentException
	 */
	protected function getType() {
		if (is_null($this->value)) {
			$this->type    = 'null';
			$this->isEmpty = true;
		}
		elseif (is_bool($this->value)) {
			$this->type = 'bool';
		}
		elseif (is_numeric($this->value)) {
			$this->type = 'numeric';
		}
		elseif (is_string($this->value)) {
			$this->type    = 'string';
			$this->isEmpty = $this->value === '';
		}
		elseif (is_array($this->value)) {
			$this->type    = 'array';
			$this->isEmpty = $this->value === [];
		}
		elseif (is_object($this->value) && $this->value instanceof File) {
			$this->type    = 'file';
			$this->isEmpty = $this->value->isGreater(1);
		}
		else {
			throw new IllegalArgumentException('The value[' . serialize($this->value) . ']Does not recognize types');
		}
	}

	/**
	 * 构造异常响应
	 * @param string $ruleName eg:between
	 * @param string $rule eg:between:2,4
	 * @param string $message eg:用户名不合法
	 * @return void
	 * @throws IllegalArgumentException
	 */
	protected function throwIllegalArgumentException(string $ruleName, string $rule, string $message = null): void {
		$msg = $message ?? ('The field[' . $this->name . '] and value[' . serialize($this->value) . '], ' .
		                    (static::$ruleReasonMap[$ruleName] ?? ('Non-compliance the rule[' . $rule . '].')));
		throw new IllegalArgumentException($msg);
	}
}
