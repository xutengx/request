<?php
declare(strict_types = 1);

use PHPUnit\Framework\TestCase;
use Xutengx\Request\Component\File;
use Xutengx\Request\Component\UploadFile;
use Xutengx\Request\Request;
use Xutengx\Tool\Tool;

final class SrcTest extends TestCase {

	protected $dir;

	public function setUp() {
		$this->dir = dirname(__DIR__) . '/storage/forTest/';
	}

	public function testRequest() {
		$dir = $this->dir;
		$this->assertInstanceOf(Tool::class, $Tool = new Tool);
		$this->assertInstanceOf(File::class, $File = new File($Tool, $dir));
		$this->assertInstanceOf(UploadFile::class, $UploadFile = new UploadFile($File));
		$this->assertInstanceOf(Request::class, $request = new Request($UploadFile, $Tool));

		$request->setParameters();

	}

	public function testInfo(){
		
	}

}


