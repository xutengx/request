<?php

declare(strict_types = 1);
namespace Xutengx\Request\Component;

use Xutengx\Tool\Tool;
use Xutengx\Request\Exception\UploadFileException;

/**
 * 每个上传的文件对象
 */
class File {
	/**
	 * type of img
	 * @var array
	 */
	const imgType = [
		'image/gif'    => 'gif',
		'image/jpeg'   => 'jpg',
		'image/pjpeg'  => 'jpg',
		'image/png'    => 'png',
		'image/x-png'  => 'png',
		'image/bmp'    => 'bmp',
		'image/x-icon' => 'ico',
	];

	/**
	 * $_FILES 中的error 对应的msg
	 * @var array
	 */
	const errorMsg = [
		'Upload success.',
		'Upload files too large.',
		'Upload files too large.',
		'Only part of the file is uploaded.',
		'No file is uploaded.',
		'Temporary folder not found.',
		'File write failure.',
	];

	/**
	 * 上传的文件名
	 * @var string
	 */
	public $name;
	/**
	 * 文件类型
	 * @var string
	 */
	public $type;
	/**
	 * 文件大小
	 * @var int
	 */
	public $size;
	/**
	 * 提交过来的键名
	 * @var string
	 */
	public $key_name;
	/**
	 * $_FILES 中的临时文件名 (post 上传文件时, 由php生成, 其他方式则没有此项)
	 * @var string
	 */
	public $tmp_name;
	/**
	 * $_FILES 中的error (post 上传文件时, 由php生成, 其他方式则没有此项)
	 * @var int
	 */
	public $error = 0;
	/**
	 * 文件保存的路径
	 * @var string
	 */
	public $saveFilename;
	/**
	 * 默认文件保存的路径
	 * @var string
	 */
	protected $defaultSavePath;
	/**
	 * 文件是否保存成功
	 * @var bool
	 */
	protected $isSave = false;
	/**
	 * 文件的内容 (非post 上传文件时, 由框架获取, post方式则没有此项)
	 * @var string
	 */
	protected $content;
	/**
	 * 文件操作对象
	 * @var Tool
	 */
	protected $tool;

	/**
	 * File constructor.
	 * @param Tool $tool
	 * @param string $defaultSavePath 默认文件保存的绝对路径
	 */
	public function __construct(Tool $tool, string $defaultSavePath) {
		$this->tool            = $tool;
		$this->defaultSavePath = rtrim($defaultSavePath, '/') . '/';
	}

	/**
	 * 当前文件是否是图像
	 * 检测 type 于 $imgType中, 且文件名后缀 于 $imgType 中
	 * @return bool
	 */
	public function isImg(): bool {
		return array_key_exists($this->type, self::imgType) && in_array($this->getExt(), self::imgType);
	}

	/**
	 * 当前文件是否过小
	 * @param int $size 文件最小字节数
	 * @return bool
	 */
	public function isGreater(int $size = 2): bool {
		return ($this->size >= $size);
	}

	/**
	 * 当前文件是否过大 8388608 8M
	 * @param int $size 文件最大字节数
	 * @return bool
	 */
	public function isLess(int $size = 8388608): bool {
		return ($this->size <= $size);
	}

	/**
	 * 是否已经保存
	 * @return bool
	 */
	public function isSave():bool {
		return $this->isSave;
	}

	/**
	 * 由上传的文件名通过字符串截取, 获得文件后缀
	 * @return string eg:png
	 */
	public function getExt(): string {
		return substr(strrchr($this->name, '.'), 1);
	}

	/**
	 * 保存上传的文件, 优先使用 \move_uploaded_file
	 * @throws UploadFileException
	 * @return bool
	 */
	public function move_uploaded_file(): bool {
		// 检查是否出错
		$this->checkError();
		// 文件名包含绝对路径
		$newFileName = $this->saveFilename ?? $this->makeFilename($this->defaultSavePath . date('Ym/d/'));

		if (!is_null($this->tmp_name)) {
			// 确保目录存在目录
			$this->tool->recursiveMakeDirectory(dirname($newFileName));
			return $this->isSave = \move_uploaded_file($this->tmp_name, $newFileName);
		}
		else {
			return $this->isSave = $this->tool->filePutContents($newFileName, $this->getContent());
		}
	}

	/**
	 * 设置路径
	 * @param string $dir 绝对路径
	 * @return string
	 */
	public function makeFilename(string $dir): string {
		return $this->saveFilename = $this->tool->generateFilename($dir, $this->getExt(), $this->key_name);
	}

	/**
	 * 根据 $this->save_path 删除保存的文件,一般情况下在数据库回滚时调用
	 * @return bool
	 */
	public function clean(): bool {
		if ($this->isSave === true)
			return unlink($this->saveFilename);
		else
			return false;
	}

	/**
	 * @param $attr
	 * @return string
	 */
	public function __get($attr) {
		if ($attr === 'content') {
			return $this->getContent();
		}
	}

	/**
	 * @param string $attr
	 * @param string $value
	 * @return string
	 */
	public function __set(string $attr, string $value) {
		if ($attr === 'content') {
			return $this->content = $value;
		}
	}

	/**
	 * 检测是否出错
	 * @throws UploadFileException
	 */
	protected function checkError() {
		if ($this->error !== 0) {
			$msg = (static::errorMsg[$this->error] ?? '') . ' code:' . (string)$this->error;
			throw new UploadFileException($msg);
		}
	}

	/**
	 * 获取本对象的内容
	 * @return string
	 */
	protected function getContent(): string {
		if (!is_null($this->tmp_name)) {
			return file_get_contents($this->tmp_name);
		}
		else {
			return $this->content;
		}
	}

}
