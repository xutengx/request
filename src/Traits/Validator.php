<?php

declare(strict_types = 1);
namespace Xutengx\Request\Traits;

use Xutengx\Request\Component\Validator as ValidatorObject;
use Xutengx\Request\Exception\IllegalArgumentException;

trait Validator {

	/**
	 * 初始化对象
	 * @param string $name
	 * @param mixed &$value
	 * @param array &$okData
	 * @return ValidatorObject
	 */
	protected function getValidatorObject(string $name, &$value,array &$okData): ValidatorObject{
		$obj = clone $this->validator;
		return $obj->init($name, $value, $okData);
	}

	/**
	 * 验证
	 * @param array $ruleArr eg:['name'=>'required|size:4']
	 * @param array $pendingData eg: ['name'=>'tony']
	 * @return array
	 * @throws IllegalArgumentException
	 */
	public function validator($ruleArr = [], $pendingData = []): array{
		// 过滤后的数据
		$okData = [];
		// 拆分字段规则
		foreach ($ruleArr as $fieldName => $ruleString){
			// 兼容极简规则
			$this->minimalistRules($fieldName, $ruleString);
			// 获取原值
			$fieldValue = $this->getOldValue($fieldName, $pendingData);
			// 构建验证对象
			$ValidatorObject = $this->getValidatorObject($fieldName, $fieldValue, $okData);
			// 规则验证
			// 不通过, 将抛出`IllegalArgumentException`
			$ValidatorObject->useRule($ruleString);
			// 赋值数组
			$okData[$fieldName] = $fieldValue;
		}
		return $okData;
	}

	/**
	 * 兼容极简规则 eg:['name'] => ['name'=>'']
	 * @param int|string &$fieldName
	 * @param string &$ruleString
	 * @return void
	 */
	protected function minimalistRules(&$fieldName, string &$ruleString): void {
		if (is_int($fieldName)) {
			$fieldName  = $ruleString;
			$ruleString = '';
		}
	}

	protected function fieldValidation($string, $rule){

	}

	/**
	 * 在data中返回fieldName字段
	 * @param string $fieldName eg:people
	 * @param array $data
	 * @return mixed
	 */
	protected function getOldValue(string $fieldName, array $data){
		return $data[$fieldName] ?? '';
	}

	/**
	 * @param $oldValue
	 * @param string $ruleString eg: required|numeric|mix:16|min:3
	 * @param string $reason
	 * @return bool
	 */
	protected function filterRules($oldValue, string $ruleString = null, &$reason = ''): bool{
		if(!empty($ruleString)){
			// 拆分规则
			$ruleArr = explode('|', $ruleString);
			// 分别验证
			foreach ($ruleArr as $rule){
				if(!$this->validationRule($oldValue, $rule, $reason)){
					return false;
				}
			}
		}
		return true;
	}

}
