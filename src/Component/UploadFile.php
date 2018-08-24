<?php

declare(strict_types = 1);
namespace Xutengx\Request\component;

use Iterator;
use Xutengx\Request\Exception\UploadFileException;

class UploadFile implements Iterator {

	protected static $errorMsg = [
		'Upload success.',
		'Upload files too large.',
		'Upload files too large.',
		'Only part of the file is uploaded.',
		'No file is uploaded.',
		'Temporary folder not found.',
		'File write failure.',
	];

	protected $_items = [];

	protected $file;

	public function __construct(File $file) {
		$this->file = $file;
	}

	/**
	 * 将$_FILES 放入 $this->file
	 * @param array $_FILES
	 * @throws UploadFileException
	 * @return void
	 */
	public function addFiles(array $_FILES): void {
		foreach ($_FILES as $k => $v) {
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
		$this->_items[$file->key_name] = $file;
	}

	/**
	 * 删除保存的文件,一般情况下在数据库回滚时调用
	 * @return void
	 */
	public function cleanAll(): void {
		foreach ($this->_items as $file) {
			$file->clean();
		}
	}

	/**
	 * 获取 File 对象
	 * @param string $attr
	 * @return File
	 */
	public function __get(string $attr): File {
		if (isset($this->_items[$attr])) {
			return $this->_items[$attr];
		}
	}

	/****************************** 以下 Iterator 实现 ******************************/

	public function rewind() {
		reset($this->_items);
	}

	public function current() {
		return current($this->_items);
	}

	public function key() {
		return key($this->_items);
	}

	public function next() {
		return next($this->_items);
	}

	public function valid() {
		return ($this->current() !== false);
	}

}
