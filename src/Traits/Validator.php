<?php

declare(strict_types = 1);
namespace Xutengx\Request\Traits;

use Closure;
use Xutengx\Request\Component\Validator as ValidatorObject;
use Xutengx\Request\Exception\IllegalArgumentException;

trait Validator {

	/**
	 * 验证
	 * @param array $ruleArr 验证规则 eg:['name'=>'required|size:4']
	 * @param array $pendingData 等待验证数组 eg: ['name'=>'tony']
	 * @return array
	 * @throws IllegalArgumentException
	 */
	public function validator($ruleArr = [], array $pendingData = null): array {
		$pendingData = $pendingData ?? $this->all();
		// 过滤后的数据
		$okData = [];
		// 拆分字段规则
		foreach ($ruleArr as $fieldName => $ruleStringOrClosure) {
			// 兼容极简规则
			$this->minimalistRules($fieldName, $ruleStringOrClosure);
			// 获取原值
			$fieldValue = $this->getOldValue($fieldName, $pendingData);
			// 构建验证对象
			$ValidatorObject = $this->getValidatorObject($fieldName, $fieldValue, $okData);
			// 规则验证
			// 不通过, 将抛出`IllegalArgumentException`
			is_string($ruleStringOrClosure) ? $ValidatorObject->useExistingRule($ruleStringOrClosure) :
				$ValidatorObject->useClosureRule($ruleStringOrClosure);
			// 赋值数组
			$okData[$fieldName] = $fieldValue;
		}
		return $okData;
	}

	/**
	 * 兼容极简规则 eg:['name'] => ['name'=>'']
	 * @param int|string &$fieldName
	 * @param string|Closure &$ruleString
	 * @return void
	 */
	protected function minimalistRules(&$fieldName, &$ruleString): void {
		if (is_int($fieldName)) {
			$fieldName  = $ruleString;
			$ruleString = '';
		}
	}

	/**
	 * 初始化对象
	 * @param string $name
	 * @param mixed &$value
	 * @param array &$okData
	 * @return ValidatorObject
	 */
	protected function getValidatorObject(string $name, &$value, array &$okData): ValidatorObject {
		$obj = clone $this->validator;
		return $obj->init($name, $value, $okData);
	}

	/**
	 * 在data中返回fieldName字段
	 * @param string $fieldName eg:people
	 * @param array $data
	 * @return mixed
	 */
	protected function getOldValue(string $fieldName, array $data) {
		return $data[$fieldName] ?? '';
	}

}
