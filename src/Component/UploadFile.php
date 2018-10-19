<?php

declare(strict_types = 1);
namespace Xutengx\Request\Component;

use ArrayAccess;
use InvalidArgumentException;
use Iterator;
use Xutengx\Request\Exception\UploadFileException;

class UploadFile implements Iterator,ArrayAccess {

	protected static $errorMsg = [
		'Upload success.',
		'Upload files too large.',
		'Upload files too large.',
		'Only part of the file is uploaded.',
		'No file is uploaded.',
		'Temporary folder not found.',
		'File write failure.',
	];

	protected $items = [];

	protected $file;

	public function __construct(File $file) {
		$this->file = $file;
	}

	/**
	 * 得到所有file对象
	 * @return array
	 */
	public function get(): array {
		return $this->items;
	}

	/**
	 * 是否存在
	 * @param string $key
	 * @return bool
	 */
	public function has(string $key): bool {
		return $this->items[$key];
	}

	/**
	 * 将$_FILES 放入 $this->file
	 * @param array $_files $_FILES
	 * @throws UploadFileException
	 * @return void
	 */
	public function addFiles(array $_files): void {
		foreach ($_files as $k => $v) {
			if ($v['error'] === 0) {
				$this->addFile([
					'key_name' => $k,
					'name'     => $v['name'],
					'type'     => $v['type'],
					'tmp_name' => $v['tmp_name'],
					'size'     => $v['size']
				]);
			}
			else {
				$msg = (static::$errorMsg[$v['error']] ?? '') . ' code:' . (string)$v['error'];
				throw new UploadFileException($msg);
			}
		}
	}

	/**
	 * 加入一个文件对象
	 * @param array $fileInfo
	 * @return void
	 */
	public function addFile(array $fileInfo): void {
		$file = clone $this->file;
		foreach ($fileInfo as $k => $v) {
			$file->{$k} = $v;
		}
		$this->items[$file->key_name] = $file;
	}

	/**
	 * 删除保存的文件,一般情况下在数据库回滚时调用
	 * @return void
	 */
	public function cleanAll(): void {
		foreach ($this->items as $file) {
			$file->clean();
		}
	}

	/**
	 * 获取 File 对象
	 * @param string $attr
	 * @return File
	 */
	public function __get(string $attr): File {
		if (isset($this->items[$attr])) {
			return $this->items[$attr];
		}
	}

	/****************************** 以下 Iterator 实现 ******************************/

	public function rewind() {
		reset($this->items);
	}

	public function current() {
		return current($this->items);
	}

	public function key() {
		return key($this->items);
	}

	public function next() {
		return next($this->items);
	}

	public function valid() {
		return ($this->current() !== false);
	}

	/****************************** 以下 ArrayAccess 实现 ******************************/

	public function offsetExists($offset) {
		return isset($this->items[$offset]);
	}

	public function offsetGet($offset) {
		return $this->items[$offset];
	}

	public function offsetSet($offset, $value) {
		if (!is_array($value)) {
			throw new InvalidArgumentException;
		}
		$value['key_name'] = $offset;
		$this->addFile($value);
	}

	public function offsetUnset($offset) {
		unset($this->items[$offset]);
	}
}
