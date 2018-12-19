<?php
declare(strict_types = 1);

use PHPUnit\Framework\TestCase;
use Xutengx\Request\Component\File;
use Xutengx\Request\Component\UploadFile;
use Xutengx\Request\Component\Validator;
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
		$this->assertInstanceOf(UploadFile::class, $UploadFile = new UploadFile($File));
		$this->assertInstanceOf(Validator::class, $Validator = new Validator());
		$this->assertInstanceOf(Request::class, $request = new Request($UploadFile, $Tool, $Validator));

		$request->setParameters();

		return $request;

	}

	/**
	 * @throws \Xutengx\Request\Exception\IllegalArgumentException
	 */
	public function testValidatorRequired() {
		$request = $this->testRequest();

		$rule  = [
			'name' => 'required'
		];
		$array = $request->validator($rule, [
			'name' => 'xuteng'
		]);
		$this->assertEquals($array, [
			'name' => 'xuteng'
		]);

		$exception = false;
		try {
			$request->validator($rule, ['age' => '15']);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}

		$this->assertEmpty($request->validator([], ['name' => '15']));
		$this->assertEquals($request->validator(['name' => ''], []), ['name' => '']);
		$this->assertEquals($request->validator(['name' => ''], ['age' => '15']), ['name' => '']);
		$this->assertEquals($request->validator(['name' => ''], ['name' => '15']), ['name' => '15']);
		$this->assertEquals($request->validator(['name'], ['name' => '15']), ['name' => '15']);
		$this->assertEquals($request->validator(['name', 'age'], ['name' => '15']), ['name' => '15', 'age' => '']);
	}

	public function testValidatorRequiredIf() {
		$request = $this->testRequest();

		$rule  = [
			'name' => 'required',
			'age'  => 'requiredIf:name,xutengx'
		];
		$array = $request->validator($rule, [
			'name' => 'xutengx',
			'age'  => '18',
		]);
		$this->assertEquals($array, [
			'name' => 'xutengx',
			'age'  => '18',
		]);

		$exception = false;
		try {
			$request->validator($rule, [
				'name' => 'xutengx',
				'age'  => '',
			]);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}

		$this->assertEquals($request->validator([
			'name' => 'required',
			'age'  => 'requiredIf:name,xutengx'
		], ['name' => '15']), ['name' => '15', 'age' => '']);

		$this->assertEquals($request->validator([
			'name' => 'required',
			'age'  => 'requiredIf:name,xutengx'
		], ['name' => 'xutengx', 'age' => '18']), ['name' => 'xutengx', 'age' => '18']);

	}

	public function testValidatorRequiredUnless() {
		$request = $this->testRequest();
		$rule    = [
			'name' => 'required',
			'age'  => 'requiredUnless:name,xutengx'
		];
		$array   = $request->validator($rule, [
			'name' => 'xutengx'
		]);
		$this->assertEquals($array, [
			'name' => 'xutengx',
			'age'  => '',
		]);

		$exception = false;
		try {
			$request->validator($rule, [
				'name' => 'xuteng',
				'age'  => '',
			]);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}

		$this->assertEquals($request->validator([
			'name' => 'required',
			'age'  => 'requiredUnless:name,xutengx'
		], ['name' => '15', 'age' => '12']), ['name' => '15', 'age' => '12']);

		$this->assertEquals($request->validator([
			'name' => 'required',
			'age'  => 'requiredUnless:name,xutengx'
		], ['name' => 'xutengx']), ['name' => 'xutengx', 'age' => '']);

	}

	public function testValidatorRequiredWith() {
		$request = $this->testRequest();
		$rule    = [
			'test1' => '',
			'test2' => '',
			'test3' => 'requiredWith:test1,test2'
		];
		$array   = $request->validator($rule, []);
		$this->assertEquals($array, [
			'test1' => '',
			'test2' => '',
			'test3' => '',
		]);

		$exception = false;
		try {
			$request->validator($rule, [
				'test1' => 'xuteng',
				'test2' => '',
				'test3' => '',
			]);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}

		$this->assertEquals($request->validator([
			'test1' => '',
			'test2' => '',
			'test3' => 'requiredWith:test1,test2'
		], [
			'test1' => 'xuteng',
			'test2' => '',
			'test3' => '123123',
		]), [
			'test1' => 'xuteng',
			'test2' => '',
			'test3' => '123123',
		]);
	}

	public function testValidatorRequiredWithAll() {
		$request = $this->testRequest();
		$rule    = [
			'test1' => '',
			'test2' => '',
			'test3' => 'requiredWithAll:test1,test2'
		];
		$array   = $request->validator($rule, []);
		$this->assertEquals($array, [
			'test1' => '',
			'test2' => '',
			'test3' => '',
		]);

		$exception = false;
		try {
			$request->validator($rule, [
				'test1' => 'xuteng',
				'test2' => 'xutengxuteng',
				'test3' => '',
			]);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}

		$this->assertEquals($request->validator([
			'test1' => '',
			'test2' => '',
			'test3' => 'requiredWithAll:test1,test2'
		], [
			'test1' => 'xuteng',
			'test2' => '',
			'test3' => '123123',
		]), [
			'test1' => 'xuteng',
			'test2' => '',
			'test3' => '123123',
		]);
	}

	public function testValidatorRequiredWithout() {
		$request = $this->testRequest();
		$rule    = [
			'test1' => '',
			'test2' => '',
			'test3' => 'requiredWithout:test1,test2'
		];
		$array   = $request->validator($rule, [
			'test1' => '123',
			'test2' => '1233',
		]);
		$this->assertEquals($array, [
			'test1' => '123',
			'test2' => '1233',
			'test3' => '',
		]);

		$exception = false;
		try {
			$request->validator($rule, [
				'test1' => 'xuteng',
				'test2' => '',
				'test3' => '',
			]);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}

		$this->assertEquals($request->validator([
			'test1' => '',
			'test2' => '',
			'test3' => 'requiredWithout:test1,test2'
		], [
			'test1' => 'xuteng',
			'test2' => '',
			'test3' => '123123',
		]), [
			'test1' => 'xuteng',
			'test2' => '',
			'test3' => '123123',
		]);
	}


	public function testValidatorRequiredWithoutAll() {
		$request = $this->testRequest();
		$rule    = [
			'test1' => '',
			'test2' => '',
			'test3' => 'requiredWithoutAll:test1,test2'
		];
		$array   = $request->validator($rule, [
			'test1' => '123',
			'test2' => '1233',
		]);
		$this->assertEquals($array, [
			'test1' => '123',
			'test2' => '1233',
			'test3' => '',
		]);

		$exception = false;
		try {
			$request->validator($rule, [
				'test1' => '',
				'test2' => '',
				'test3' => '',
			]);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}

		$this->assertEquals($request->validator([
			'test1' => '',
			'test2' => '',
			'test3' => 'requiredWithoutAll:test1,test2'
		], [
			'test1' => '',
			'test2' => '',
			'test3' => '123123',
		]), [
			'test1' => '',
			'test2' => '',
			'test3' => '123123',
		]);
	}

	public function testSame() {
		$request = $this->testRequest();
		$exception = false;
		try {
			$this->assertEquals($request->validator(['name1' => '', 'name2' => 'same:name1'],
				['name1' => 'test_name1', 'name2' => 'test_name2']), ['name1' => 'test_name', 'name2' => 'test_name']);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}
		$this->assertEquals($request->validator(['name1' => '', 'name2' => 'same:name1'],
			['name1' => 'test_name', 'name2' => 'test_name']), ['name1' => 'test_name', 'name2' => 'test_name']);
		$this->assertEquals($request->validator(['name1' => '', 'name2' => 'same:name1'],
			['name1' => 'test_name', 'name2' => '']), ['name1' => 'test_name', 'name2' => '']);
	}

	public function testSize() {
		$request = $this->testRequest();
		$exception = false;
		try {
			$this->assertEquals($request->validator(['name1' => '', 'name2' => 'size:16,string'],
				['name1' => 'test_name1', 'name2' => 'test_name2']), ['name1' => 'test_name', 'name2' => 'test_name']);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}
		$this->assertEquals($request->validator(['name1' => '', 'name2' => 'size:6,string'],
			['name1' => 'test_name', 'name2' => 'werfvd']), ['name1' => 'test_name', 'name2' => 'werfvd']);
		$this->assertEquals($request->validator(['name1' => '', 'name2' => 'size:6,int'],
			['name1' => 'test_name', 'name2' => '6']), ['name1' => 'test_name', 'name2' => '6']);
		$this->assertEquals($request->validator(['name1' => '', 'name2' => 'size:6,int'],
			['name1' => 'test_name', 'name2' => '']), ['name1' => 'test_name', 'name2' => '']);

	}

	public function testString() {
		$request = $this->testRequest();
		$exception = false;
		try {
			$this->assertEquals($request->validator(['name1' => '', 'name2' => 'string'],
				['name1' => 'test_name1', 'name2' => 123]), ['name1' => 'test_name1', 'name2' => 123]);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}
		$this->assertEquals($request->validator(['name1' => '', 'name2' => 'string'],
			['name1' => 'test_name1', 'name2' => null]), ['name1' => 'test_name1', 'name2' => null]);
		$this->assertEquals($request->validator(['name1' => '', 'name2' => 'string'],
			['name1' => 'test_name', 'name2' => '']), ['name1' => 'test_name', 'name2' => '']);
		$this->assertEquals($request->validator(['name1' => '', 'name2' => 'string'],
			['name1' => 'test_name', 'name2' => 'wwww']), ['name1' => 'test_name', 'name2' => 'wwww']);

	}

	public function testTimezone() {
		$request = $this->testRequest();
		$exception = false;
		try {
			$this->assertEquals($request->validator(['name1' => '', 'timezone' => 'timezone'],
				['name1' => 'test_name1', 'timezone' => 123]), ['name1' => 'test_name1', 'timezone' => 123]);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}

		$this->assertEquals($request->validator(['name1' => '', 'timezone' => 'timezone'],
			['name1' => 'test_name1', 'timezone' => 'Africa/Bamako']), ['name1' => 'test_name1', 'timezone' => 'Africa/Bamako']);
		$this->assertEquals($request->validator(['name1' => '', 'timezone' => 'timezone'],
			['name1' => 'test_name1', 'timezone' => '']), ['name1' => 'test_name1', 'timezone' => '']);

	}

	public function testUrl() {
		$request = $this->testRequest();
		$exception = false;
		try {
			$this->assertEquals($request->validator(['name1' => '', 'blog' => 'url'],
				['name1' => 'test_name1', 'blog' => 'www.baidu.com']), ['name1' => 'test_name1', 'blog' => 'www.baidu.com']);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}

		$this->assertEquals($request->validator(['name1' => '', 'blog' => 'url'],
			['name1' => 'test_name1', 'blog' => '']), ['name1' => 'test_name1', 'blog' => '']);
		$this->assertEquals($request->validator(['name1' => '', 'blog' => 'url'],
			['name1' => 'test_name1', 'blog' => 'https://www.baidu.com']), ['name1' => 'test_name1', 'blog' => 'https://www.baidu.com']);
		$this->assertEquals($request->validator(['name1' => '', 'blog' => 'url'],
			['name1' => 'test_name1', 'blog' => 'http://www.baidu.com:8086/page/index.html']), ['name1' => 'test_name1',
		                                                                                     'blog' =>	'http://www.baidu.com:8086/page/index.html']);

	}

	/**
	 * @throws \Xutengx\Request\Exception\IllegalArgumentException
	 */
	public function testValidatorAccepted() {
		$request   = $this->testRequest();
		$exception = false;
		try {
			$request->validator(['is_ok' => 'accepted'], ['is_ok' => 'ok']);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}
		$this->assertEquals($request->validator(['is_ok' => 'accepted'], ['is_ok' => 'on']), ['is_ok' => 'on']);
		$this->assertEquals($request->validator(['is_ok' => 'accepted'], ['is_ok' => 'yes']), ['is_ok' => 'yes']);
		$this->assertEquals($request->validator(['is_ok' => 'accepted'], ['is_ok' => '1']), ['is_ok' => '1']);
		$this->assertEquals($request->validator(['is_ok' => 'accepted'], ['is_ok' => 1]), ['is_ok' => 1]);
		$this->assertEquals($request->validator(['is_ok' => 'accepted'], ['is_ok' => 'true']), ['is_ok' => 'true']);
		$this->assertEquals($request->validator(['is_ok' => 'accepted'], ['is_ok' => true]), ['is_ok' => true]);
	}

	public function testValidatorActiveUrl() {
		$request   = $this->testRequest();
		$exception = false;
		try {
			$request->validator(['url' => 'activeUrl'], ['url' => 'www.b1aidu.com']);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}
		$this->assertEquals($request->validator(['url' => 'activeUrl'], ['url' => '']), ['url' => '']);
		$this->assertEquals($request->validator(['url' => 'activeUrl'], ['url' => 'www.baidu.com']),
			['url' => 'www.baidu.com']);
		$this->assertEquals($request->validator(['url' => ''], ['url' => 'yes']), ['url' => 'yes']);
	}

	public function testValidatorNumeric() {
		$request   = $this->testRequest();
		$exception = false;
		try {
			$request->validator(['age' => 'numeric'], ['age' => '-18a.1']);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}
		$this->assertEquals($request->validator(['age' => 'numeric'], ['age' => '']), ['age' => '']);
		$this->assertEquals($request->validator(['age' => 'numeric'], ['age' => '18']), ['age' => '18']);
		$this->assertEquals($request->validator(['age' => 'numeric'], ['age' => '18.123']), ['age' => '18.123']);
		$this->assertEquals($request->validator(['age' => 'numeric'], ['age' => '-18.123']), ['age' => '-18.123']);
		$this->assertEquals($request->validator(['age' => 'numeric'], ['age' => -18]), ['age' => -18]);
		$this->assertEquals($request->validator(['age' => 'numeric'], ['age' => 18.354]), ['age' => 18.354]);
		$this->assertEquals($request->validator(['age' => 'numeric'], ['age' => 18]), ['age' => 18]);
		$this->assertEquals($request->validator(['age' => 'numeric'], ['age' => 0x123]), ['age' => 291]);
		$this->assertEquals($request->validator(['age' => 'numeric'], ['age' => 0x123]), ['age' => 0x123]);
	}

	public function testValidatorAfter() {
		$request   = $this->testRequest();
		$exception = false;
		try {
			$request->validator(['birthday' => 'after:1999-11-18'], ['birthday' => '1999-11-17']);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}
		$this->assertEquals($request->validator(['birthday' => 'after:1999-11-18'], ['birthday' => '1999-11-19']),
			['birthday' => '1999-11-19']);
		$this->assertEquals($request->validator(['birthday' => 'after:1999-11-18'], ['birthday' => '']),
			['birthday' => '']);
	}

	public function testValidatorAlpha() {
		$request   = $this->testRequest();
		$exception = false;
		try {
			$request->validator(['account' => 'alpha'], ['account' => 'qw1eqw']);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}
		$this->assertEquals($request->validator(['account' => 'alpha'], ['account' => 'qweqw']),
			['account' => 'qweqw']);
		$this->assertEquals($request->validator(['account' => 'alpha'], ['account' => '']), ['account' => '']);
	}

	public function testValidatorRegularMatch() {
		$request   = $this->testRequest();
		$exception = false;
		try {
			$request->validator(['account' => 'regex:/^[a-zA-Z]$/'], ['account' => 'qwe']);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}
		$this->assertEquals($request->validator(['account' => 'regex:/^[a-zA-Z]$/'], ['account' => 'a']),
			['account' => 'a']);
	}

	public function testValidatorAlphaDash() {
		$request   = $this->testRequest();
		$exception = false;
		try {
			$request->validator(['account' => 'alphaDash'], ['account' => 'qwe|123']);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}
		$this->assertEquals($request->validator(['text' => 'alphaDash'], ['text' => 'e_-123']), [
			'text' => 'e_-123'
		]);
		$this->assertEquals($request->validator(['text' => 'alphaDash'], ['text' => '']), [
			'text' => ''
		]);
	}

	public function testValidatorAlphaNum() {
		$request   = $this->testRequest();
		$exception = false;
		try {
			$request->validator(['account' => 'alphaNum'], ['account' => 'qwe_123']);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}
		$this->assertEquals($request->validator(['text' => 'alphaDash'], ['text' => 'e3']), [
			'text' => 'e3'
		]);
		$this->assertEquals($request->validator(['text' => 'alphaDash'], ['text' => '']), [
			'text' => ''
		]);
	}

	public function testValidatorArray() {
		$request   = $this->testRequest();
		$exception = false;
		try {
			$request->validator(['details' => 'array'], ['details' => 'qwe_123']);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}
		$this->assertEquals($request->validator(['details' => 'array'], ['details' => []]), [
			'details' => []
		]);
		$this->assertEquals($request->validator(['details' => 'array'], ['details' => [123, 123]]), [
			'details' => [123, 123]
		]);
	}

	public function testValidatorBefore() {
		$request   = $this->testRequest();
		$exception = false;
		try {
			$request->validator(['birthday' => 'before:1999-11-18'], ['birthday' => '1999-11-18']);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}
		$this->assertEquals($request->validator(['birthday' => 'before:1999-11-22'], ['birthday' => '1999-11-19']),
			['birthday' => '1999-11-19']);
		$this->assertEquals($request->validator(['birthday' => 'before:1999-11-22'], ['birthday' => '']), [
			'birthday' => ''
		]);
	}

	public function testValidatorBetween() {
		$request   = $this->testRequest();
		$exception = false;
		try {
			$request->validator(['age' => 'between:12,17'], ['age' => '11']);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}
		$this->assertEquals($request->validator(['age' => 'between:12,17'], ['age' => '13']), ['age' => '13']);
		$this->assertEquals($request->validator(['age' => 'between:12,17'], ['age' => '12']), ['age' => '12']);
		$this->assertEquals($request->validator(['age' => 'between:12,17'], ['age' => '17']), ['age' => '17']);
		$this->assertEquals($request->validator(['name' => 'between:2,4'], ['name' => 'xu']), ['name' => 'xu']);
		$this->assertEquals($request->validator(['name' => 'between:2,4'], ['name' => 'xu22']), ['name' => 'xu22']);
		$this->assertEquals($request->validator(['name' => 'between:2,4'], ['name' => '']), ['name' => '']);
		$this->assertEquals($request->validator(['text' => 'between:2,4'], ['text' => '3']), ['text' => '3']);
		$this->assertEquals($request->validator(['text' => 'between:2,4'], ['text' => 'a33']), ['text' => 'a33']);

		$exception = false;
		try {
			// 当待验证值为数字字符串时, 当做数字处理
			$request->validator(['text' => 'between:2,4'], ['text' => '33']);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}
		// 当待验证值为数字字符串时, 指定当做string来处理
		$this->assertEquals($request->validator(['text' => 'between:2,4,string'], ['text' => '33']), ['text' => '33']);
	}

	public function testValidatorConfirmed() {
		$request   = $this->testRequest();
		$exception = false;
		try {
			$request->validator(['age' => 'between:12,17', 'age_confirmed' => 'confirmed:age'], [
				'age'           => '13',
				'age_confirmed' => '12'
			]);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}
		$exception = false;
		try {
			$request->validator(['age_confirmed' => 'between:12,17', 'age' => 'confirmed:age'], [
				'age'           => '13',
				'age_confirmed' => '13'
			]);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}
		$this->assertEquals($request->validator(['age' => 'between:12,17', 'age_confirmed' => 'confirmed:age'], [
			'age'           => '13',
			'age_confirmed' => '13'
		]), [
			'age'           => '13',
			'age_confirmed' => '13'
		]);
	}

	public function testValidatorDate() {
		$request   = $this->testRequest();
		$exception = false;
		try {
			$request->validator(['birthday' => 'date'], ['birthday' => '2018-01-101']);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}
		$this->assertEquals($request->validator(['birthday' => 'date'], [
			'birthday' => '2018-01-01'
		]), [
			'birthday' => '2018-01-01'
		]);
	}

	public function testValidatorDateEquals() {
		$request = $this->testRequest();
		$this->assertEquals($request->validator(['birthday' => 'dateEquals:2018-01-10'], [
			'birthday' => '2018-01-10'
		]), [
			'birthday' => '2018-01-10'
		]);
		$exception = false;
		try {
			$request->validator(['birthday' => 'dateEquals:2018-01-101'], ['birthday' => '2018-01-101']);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}
	}

	public function testValidatorDateFormat() {
		$request = $this->testRequest();
		$this->assertEquals($request->validator(['birthday' => 'dateFormat:Y-m-d H:i:s'], [
			'birthday' => '2018-01-10 12:12:33'
		]), [
			'birthday' => '2018-01-10 12:12:33'
		]);
		$this->assertEquals($request->validator(['birthday' => 'dateFormat:Y-m-d H:s:i'],
			['birthday' => '2018-01-10 12:12:33']), [
			'birthday' => '2018-01-10 12:12:33'
		]);
		$exception = false;
		try {
			$request->validator(['birthday' => 'dateFormat:Y-m-d H:i:s'], ['birthday' => '2018/01/10 12:12:33']);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}

	}

	public function testValidatorDifferent() {
		$request   = $this->testRequest();
		$exception = false;
		try {
			$request->validator(['age' => 'between:12,17', 'age_different' => 'different:age'], [
				'age'           => '13',
				'age_different' => '13'
			]);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}

		$this->assertEquals($request->validator(['age' => 'between:12,17', 'age_different' => 'different:age'], [
			'age'           => '13',
			'age_different' => '14'
		]), [
			'age'           => '13',
			'age_different' => '14'
		]);
	}

	public function testValidatorDigits() {
		$request   = $this->testRequest();
		$exception = false;
		try {
			$request->validator(['phone' => 'digits:11'], ['phone' => 'a1361919191']);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}
		$exception = false;
		try {
			$request->validator(['phone' => 'digits:11'], ['phone' => '1361919191']);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}
		$this->assertEquals($request->validator(['phone' => 'digits:11'], ['phone' => '13619191919']),
			['phone' => '13619191919']);
	}

	public function testValidatorDigitsBetween() {
		$request   = $this->testRequest();
		$exception = false;
		try {
			$request->validator(['phone' => 'digitsBetween:7,11'], ['phone' => 'a1361919191']);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}
		$exception = false;
		try {
			$request->validator(['phone' => 'digitsBetween:7,11'], ['phone' => '113344']);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}
		$this->assertEquals($request->validator(['phone' => 'digitsBetween:7,11'], ['phone' => '13619191919']),
			['phone' => '13619191919']);
	}

	public function testValidatorEmail() {
		$request   = $this->testRequest();
		$exception = false;
		try {
			$request->validator(['email' => 'email'], ['email' => '12345@122']);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}
		$this->assertEquals($request->validator(['email' => 'email'], ['email' => '12345122@qq.com']),
			['email' => '12345122@qq.com']);
	}

	public function testValidatorFile() {

	}

	public function testValidatorCompare() {
		$request = $this->testRequest();
		$this->assertEquals($request->validator([
			'age'  => 'between:1,11,numeric',
			'age2' => 'confirmed:age'
		], [
			'age'  => '9',
			'age2' => '9'
		]), [
			'age'  => '9',
			'age2' => '9'
		]);
		$this->assertEquals($request->validator([
			'age'  => 'between:1,11,numeric',
			'age2' => 'gt:age'
		], [
			'age'  => '2',
			'age2' => '9'
		]), [
			'age'  => '2',
			'age2' => '9'
		]);
		$this->assertEquals($request->validator([
			'age'  => 'between:1,11,numeric',
			'age2' => 'gte:age'
		], [
			'age'  => '9',
			'age2' => '9'
		]), [
			'age'  => '9',
			'age2' => '9'
		]);
		$this->assertEquals($request->validator([
			'age'  => 'between:1,11,numeric',
			'age2' => 'gte:age|between:1,11,numeric'
		], [
			'age'  => '9',
			'age2' => '11'
		]), [
			'age'  => '9',
			'age2' => '11'
		]);
		$this->assertEquals($request->validator([
			'age'  => 'between:1,11,numeric',
			'age2' => 'lt:age|between:1,11,numeric'
		], [
			'age'  => '9',
			'age2' => '7'
		]), [
			'age'  => '9',
			'age2' => '7'
		]);
		$this->assertEquals($request->validator([
			'age'  => 'between:1,11,numeric',
			'age2' => 'lte:age|between:1,11,numeric'
		], [
			'age'  => '9',
			'age2' => '9'
		]), [
			'age'  => '9',
			'age2' => '9'
		]);
		$this->assertEquals($request->validator([
			'age'  => 'between:1,11,numeric',
			'age2' => 'different:age|between:1,11,numeric'
		], [
			'age'  => '9',
			'age2' => '8'
		]), [
			'age'  => '9',
			'age2' => '8'
		]);
		$this->assertEquals($request->validator([
			'age'  => 'between:1,11,numeric',
			'age2' => 'different:age,string|between:1,11,numeric'
		], [
			'age'  => '9',
			'age2' => '11'
		]), [
			'age'  => '9',
			'age2' => '11'
		]);
		$exception = false;
		try {
			$request->validator([
				'age'  => 'between:1,11,numeric',
				'age2' => 'different:age,string|between:1,11,numeric'
			], [
				'age'  => '9',
				'age2' => '8'
			]);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}

	}

	public function testValidatorIn() {
		$request   = $this->testRequest();
		$exception = false;
		try {
			$request->validator(['sex' => 'in:男,女'], ['sex' => '未知']);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}
		$this->assertEquals($request->validator(['sex' => 'in:男,女'], ['sex' => '男']), ['sex' => '男']);
		$this->assertEquals($request->validator(['sex' => 'in:男,女'], ['sex' => '']), ['sex' => '']);
	}

	public function testValidatorNotIn() {
		$request   = $this->testRequest();
		$exception = false;
		try {
			$request->validator(['sex' => 'notIn:男,女'], ['sex' => '男']);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}
		$this->assertEquals($request->validator(['sex' => 'notIn:男,女'], ['sex' => '未知']), ['sex' => '未知']);
		$this->assertEquals($request->validator(['sex' => 'notIn:男,女'], ['sex' => '']), ['sex' => '']);
	}

	public function testValidatorInteger() {
		$request   = $this->testRequest();
		$exception = false;
		try {
			$request->validator([
				'age'  => 'integer',
				'age2' => 'different:age,string|between:1,11,numeric'
			], [
				'age'  => '-9',
				'age2' => '11'
			]);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}
		$this->assertEquals($request->validator([
			'age'  => 'integer',
			'age2' => 'different:age,string|between:1,11,numeric'
		], [
			'age'  => '9',
			'age2' => '11'
		]), [
			'age'  => '9',
			'age2' => '11'
		]);
		$this->assertEquals($request->validator([
			'age'  => 'integer',
			'age2' => 'different:age,string|between:1,11,numeric'
		], [
			'age'  => '9',
			'age2' => '11'
		]), [
			'age'  => '9',
			'age2' => '11'
		]);

	}

	public function testValidatorIp() {
		$request   = $this->testRequest();
		$exception = false;
		try {
			$request->validator(['ip' => 'ip'], ['ip' => '256.257.258.2']);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}
		$this->assertEquals($request->validator(['ip' => 'ip'], ['ip' => '2001:DB8:0:23:8:800:200C:417A']),
			['ip' => '2001:DB8:0:23:8:800:200C:417A']);
		$this->assertEquals($request->validator(['ip' => 'ip'], ['ip' => '127.0.0.2']), ['ip' => '127.0.0.2']);
		$this->assertEquals($request->validator(['ip' => 'ip'], ['ip' => '218.17.55.229']), ['ip' => '218.17.55.229']);
	}

	public function testValidatorIpv4() {
		$request   = $this->testRequest();
		$exception = false;
		try {
			$request->validator(['ip' => 'ipv4'], ['ip' => '2001:DB8:0:23:8:800:200C:417A']);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}
		$this->assertEquals($request->validator(['ip' => 'ip'], ['ip' => '127.0.0.2']), ['ip' => '127.0.0.2']);
		$this->assertEquals($request->validator(['ip' => 'ip'], ['ip' => '218.17.55.229']), ['ip' => '218.17.55.229']);
	}

	public function testValidatorIpv6() {
		$request   = $this->testRequest();
		$exception = false;
		try {
			$request->validator(['ip' => 'ipv6'], ['ip' => '218.17.55.229']);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}
		$this->assertEquals($request->validator(['ip' => 'ipv6'], ['ip' => '2001:DB8:0:23:8:800:200C:417A']),
			['ip' => '2001:DB8:0:23:8:800:200C:417A']);
		$this->assertEquals($request->validator(['ip' => 'ipv6'], ['ip' => 'FF01::1101']), ['ip' => 'FF01::1101']);
	}

	public function testValidatorJson() {
		$request   = $this->testRequest();
		$exception = false;
		try {
			$request->validator(['info' => 'json'], ['info' => '{"name":"xiaoming","age":"2}']);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}
		$this->assertEquals($request->validator(['info' => 'json'], ['info' => '{"name":"xiaoming","age":"22"}']),
			['info' => '{"name":"xiaoming","age":"22"}']);
	}

	public function testValidatorMax() {
		$request   = $this->testRequest();
		$exception = false;
		try {
			$request->validator(['age' => 'max:24'], ['age' => '25']);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}
		$this->assertEquals($request->validator(['age' => 'max:24'], ['age' => '22']), ['age' => '22']);
	}

	public function testValidatorMin() {
		$request   = $this->testRequest();
		$exception = false;
		try {
			$request->validator(['age' => 'min:24'], ['age' => '12']);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}
		$this->assertEquals($request->validator(['age' => 'min:24'], ['age' => '25']), ['age' => '25']);
	}

	public function testValidatorNotRegex() {
		$request   = $this->testRequest();
		$exception = false;
		try {
			$request->validator(['age' => 'notRegex:/^[[0-9]*$/'], ['age' => '12']);
		} catch (\Xutengx\Request\Exception\IllegalArgumentException $e) {
			$exception = true;
		} finally {
			$this->assertTrue($exception);
		}
		$this->assertEquals($request->validator(['age' => 'notRegex:/^[[a-zA-Z0-9]*$/'], ['age' => '#25']),
			['age' => '#25']);
	}
}


