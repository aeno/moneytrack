<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\console\command;

use \Phar;
use \lithium\console\command\Library;
use \lithium\core\Libraries;
use \lithium\console\Request;

class LibraryTest extends \lithium\test\Unit {

	public $request;

	protected $_backup = array();

	protected $_testPath = null;

	public function setUp() {
		$this->_backup['cwd'] = getcwd();
		$this->_backup['_SERVER'] = $_SERVER;
		$_SERVER['argv'] = array();
		$this->_testPath = LITHIUM_APP_PATH . '/resources/tmp/tests';

		chdir($this->_testPath);
		Libraries::add('library_test', array('path' => $this->_testPath . '/library_test'));
		Libraries::add('plugin', array(
			'library_test_plugin' => array('path' => $this->_testPath . '/library_test_plugin')
		));

		$this->classes = array(
			'service' => '\lithium\tests\mocks\console\command\MockLibraryService',
			'response' => '\lithium\tests\mocks\console\MockResponse'
		);
		$this->request = new Request(array('input' => fopen('php://temp', 'w+')));
		$this->library = new Library(array(
			'request' => $this->request, 'classes' => $this->classes
		));
		$this->library->conf = $this->_conf = $this->_testPath . '/library.json';
	}

	public function tearDown() {
		$_SERVER = $this->_backup['_SERVER'];
		chdir($this->_backup['cwd']);
		Libraries::remove('library_test');
		unset($this->library, $this->request);
	}

	public function testConfigServer() {
		$result = $this->library->config('server', 'lab.lithify.me');
		$this->assertTrue($result);

		$expected = array('servers' => array(
			'lab.lithify.me' => true
		));
		$result = json_decode(file_get_contents($this->_conf), true);
		$this->assertEqual($expected, $result);
	}

	public function testExtract() {
		$this->library->library = 'library_test';

		$expected = true;
		$result = $this->library->extract($this->_testPath . '/library_test');
		$this->assertEqual($expected, $result);

		$expected = "library_test created in {$this->_testPath} from ";
		$expected .= LITHIUM_LIBRARY_PATH . "/lithium/console/command/create/template/app.phar.gz\n";
		$result = $this->library->response->output;
		$this->assertEqual($expected, $result);
	}

	public function testArchive() {
		$this->skipIf(
			ini_get('phar.readonly') == '1',
			'Skipped test {:class}::{:function}() - INI setting phar.readonly = On'
		);

		$this->library->library = 'library_test';

		$expected = true;
		$result = $this->library->archive(
			$this->_testPath . '/library_test',
			$this->_testPath . '/library_test'
		);
		$this->assertEqual($expected, $result);

		$expected = "library_test.phar.gz created in {$this->_testPath} from ";
		$expected .= "{$this->_testPath}/library_test\n";
		$result = $this->library->response->output;
		$this->assertEqual($expected, $result);

		Phar::unlinkArchive($this->_testPath . '/library_test.phar');
	}

	public function testExtractWithFullPaths() {
		$this->skipIf(
			!file_exists($this->_testPath . '/library_test.phar.gz'),
			'Skipped test {:class}::{:function}() - depends on {:class}::testArchive()'
		);
		$this->library->library = 'library_test';

		$expected = true;
		$result = $this->library->extract(
			$this->_testPath . '/library_test.phar.gz', $this->_testPath . '/new'
		);
		$this->assertEqual($expected, $result);

		$this->assertTrue(file_exists($this->_testPath . '/new'));

		$expected = "new created in {$this->_testPath} from ";
		$expected .= "{$this->_testPath}/library_test.phar.gz\n";
		$result = $this->library->response->output;
		$this->assertEqual($expected, $result);

		Phar::unlinkArchive($this->_testPath . '/library_test.phar.gz');
	}

	public function testArchiveNoLibrary() {
		$this->skipIf(
			ini_get('phar.readonly') == '1',
			'Skipped test {:class}::{:function}() - INI setting phar.readonly = On'
		);

		chdir('new');
		$app = new Library(array(
			'request' => new Request(), 'classes' => $this->classes
		));
		$app->library = 'does_not_exist';

		$expected = true;
		$result = $app->archive();
		$this->assertEqual($expected, $result);

		$expected = "new.phar.gz created in {$this->_testPath} from ";
		$expected .= "{$this->_testPath}/new\n";
		$result = $app->response->output;
		$this->assertEqual($expected, $result);

		Phar::unlinkArchive($this->_testPath . '/new.phar');
		Phar::unlinkArchive($this->_testPath . '/new.phar.gz');
		$this->_cleanUp('tests/new');
		rmdir($this->_testPath . '/new');
	}

	public function testExtractWhenLibraryDoesNotExist() {
		chdir($this->_testPath);
		$app = new Library(array(
			'request' => new Request(), 'classes' => $this->classes
		));
		$app->library = 'does_not_exist';

		$expected = true;
		$result = $app->extract();
		$this->assertEqual($expected, $result);

		$this->assertTrue(file_exists($this->_testPath . '/new'));

		$expected = "new created in {$this->_testPath} from ";
		$expected .= LITHIUM_LIBRARY_PATH . "/lithium/console/command/create/template/app.phar.gz\n";
		$result = $app->response->output;
		$this->assertEqual($expected, $result);

		$this->_cleanUp();
	}

	public function testExtractPlugin() {
		$this->library->library = 'library_plugin_test';

		$expected = true;
		$result = $this->library->extract('plugin', $this->_testPath . '/library_test_plugin');
		$this->assertEqual($expected, $result);

		$expected = "library_test_plugin created in {$this->_testPath} from ";
		$expected .= LITHIUM_LIBRARY_PATH
			. "/lithium/console/command/create/template/plugin.phar.gz\n";
		$result = $this->library->response->output;
		$this->assertEqual($expected, $result);
	}

	public function testFormulate() {
		$result = $this->library->formulate(
			$this->_testPath . '/library_test_plugin'
		);
		$this->assertTrue($result);

		$result = file_exists(
			$this->_testPath
			. '/library_test_plugin/config/library_test_plugin.json'
		);
		$this->assertTrue($result);
	}

	public function testPush() {
		$this->skipIf(
			ini_get('phar.readonly') == '1',
			'Skipped test {:class}::{:function}() - INI setting phar.readonly = On'
		);
		$result = $this->library->archive(
			$this->_testPath . '/library_test_plugin',
			$this->_testPath . '/library_test_plugin'
		);
		$this->assertTrue($result);

		$expected = "library_test_plugin.phar.gz created in {$this->_testPath} from ";
		$expected .= "{$this->_testPath}/library_test_plugin\n";
		$result = $this->library->response->output;
		$this->assertEqual($expected, $result);

		$result = file_exists($this->_testPath . '/library_test_plugin.phar.gz');
		$this->assertTrue($result);

		$result = $this->library->push('library_test_plugin');

		$result = is_dir($this->_testPath . '/library_test_plugin');
		$this->assertTrue($result);

		$this->library->username = 'gwoo';
		$this->library->password = 'password';
		$request = $this->library->push('library_test_plugin');

		$expected = array('method' => 'Basic', 'username' => 'gwoo', 'password' => 'password');
		$result = $request->auth;
		$this->assertEqual($expected, $result);

		$result = is_dir($this->_testPath . '/library_test_plugin');
		$this->assertTrue($result);

		$this->_cleanUp('tests/library_test_plugin');
		rmdir($this->_testPath . '/library_test_plugin');
	}

	public function testInstall() {
		$this->skipIf(
			ini_get('phar.readonly') == '1',
			'Skipped test {:class}::{:function}() - relies on {:class}::testPush()'
		);
		$this->library->path = $this->_testPath;
		$result = $this->library->install('library_test_plugin');
		$this->assertTrue($result);

		$result = file_exists($this->_testPath . '/library_test_plugin.phar.gz');
		$this->assertTrue($result);

		$result = is_dir($this->_testPath . '/library_test_plugin');
		$this->assertTrue($result);

		Phar::unlinkArchive($this->_testPath . '/library_test_plugin.phar');
		Phar::unlinkArchive($this->_testPath . '/library_test_plugin.phar.gz');
		$this->_cleanUp();
	}

	public function testFind() {
		$this->library->find();

$expected = <<<'test'
--------------------------------------------------------------------------------
lab.lithify.me > li3_lab
--------------------------------------------------------------------------------
the li3 plugin client/server
Version: 1.0
Created: 2009-11-30
--------------------------------------------------------------------------------
lab.lithify.me > library_test_plugin
--------------------------------------------------------------------------------
an li3 plugin example
Version: 1.0
Created: 2009-11-30

test;
		$result = $this->library->response->output;
		$this->assertEqual($expected, $result);
	}

	public function testForceArchive() {
		$this->skipIf(
			ini_get('phar.readonly') == '1',
			'Skipped test {:class}::{:function}() - INI setting phar.readonly = On'
		);
		$result = $this->library->extract('plugin', $this->_testPath . '/library_test_plugin');
		$this->assertTrue($result);

		$this->library->response->output = null;
		$result = $this->library->archive(
			$this->_testPath . '/library_test_plugin',
			$this->_testPath . '/library_test_plugin'
		);
		$this->assertTrue($result);

		$expected = "library_test_plugin.phar.gz created in {$this->_testPath} from ";
		$expected .= "{$this->_testPath}/library_test_plugin\n";
		$result = $this->library->response->output;
		$this->assertEqual($expected, $result);

		$this->library->response->output = null;
		$result = $this->library->archive(
			$this->_testPath . '/library_test_plugin',
			$this->_testPath . '/library_test_plugin'
		);
		$this->assertFalse($result);

		$expected = "library_test_plugin.phar already exists in {$this->_testPath}\n";
		$result = $this->library->response->error;
		$this->assertEqual($expected, $result);


		$this->library->force = true;
		$this->library->response->output = null;
		$result = $this->library->archive(
			$this->_testPath . '/library_test_plugin',
			$this->_testPath . '/library_test_plugin'
		);
		$this->assertTrue($result);

		$expected = "library_test_plugin.phar.gz created in {$this->_testPath} from ";
		$expected .= "{$this->_testPath}/library_test_plugin\n";
		$result = $this->library->response->output;
		$this->assertEqual($expected, $result);

		unlink($this->_testPath . '/library_test_plugin.phar');

		$this->library->force = false;
		$this->library->response->output = null;
		$this->library->response->error = null;
		$result = $this->library->archive(
			$this->_testPath . '/library_test_plugin',
			$this->_testPath . '/library_test_plugin'
		);
		$this->assertFalse($result);

		$expected = "library_test_plugin.phar.gz already exists in {$this->_testPath}\n";
		$result = $this->library->response->error;
		$this->assertEqual($expected, $result);

		$this->library->force = true;
		$this->library->response->output = null;
		$this->library->response->error = null;
		$result = $this->library->archive(
			$this->_testPath . '/library_test_plugin',
			$this->_testPath . '/library_test_plugin'
		);
		$this->assertTrue($result);

		$expected = "library_test_plugin.phar.gz created in {$this->_testPath} from ";
		$expected .= "{$this->_testPath}/library_test_plugin\n";
		$result = $this->library->response->output;
		$this->assertEqual($expected, $result);

		Phar::unlinkArchive($this->_testPath . '/library_test_plugin.phar');
		Phar::unlinkArchive($this->_testPath . '/library_test_plugin.phar.gz');
		$this->_cleanUp();
	}
}

?>