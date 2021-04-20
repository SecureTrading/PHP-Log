<?php

namespace Securetrading\Log\Tests\Unit;

use \org\bovigo\vfs\vfsStream;

require_once(__DIR__ . '/helpers/CoreMocks.php');

class FileWriterTest extends \Securetrading\Unittest\UnittestAbstract {
  protected function _newFileWriter($logFileName, $logFileDirectory, $logArchiveDirectory) {
    return new \Securetrading\Log\FileWriter($logFileName, $logFileDirectory, $logArchiveDirectory);
  }

  public function tearDown() : void {
    \Securetrading\Unittest\CoreMocker::releaseCoreMocks();
  }

  /**
   * @dataProvider provider_addTrailingDirSeparator
   */
  public function test_addTrailingDirSeparator($input, $expectedReturnValue) {
    $fileWriter = $this->_newFileWriter('log', 'logpath', 'archivepath');
    $actualReturnValue = $this->_($fileWriter, '_addTrailingDirSeparator', $input);
    $this->assertEquals($expectedReturnValue, $actualReturnValue);
  }

  public function provider_addTrailingDirSeparator() {
    $this->_addDataSet('file/path', 'file/path' . DIRECTORY_SEPARATOR);
    $this->_addDataSet('file/path' . DIRECTORY_SEPARATOR, 'file/path' . DIRECTORY_SEPARATOR);
    return $this->_getDataSets();
  }

  /**
   * 
   */
  public function test_init_CreatesLogDir() {
    $filesystemRoot = vfsStream::setup('root_dir', 0777, array());
    $fileWriter = $this->_newFileWriter('log', 'vfs://root_dir/logs', 'vfs://root_dir/archived_logs');

    $this->assertTrue(null === $filesystemRoot->getChild('logs'));
    $this->_($fileWriter, '_init');
    $this->assertTrue(file_exists($filesystemRoot->getChild('logs')->url()));
  }

  /**
   * 
   */
  public function test_init_CreatesLogFile() {
    $filesystemRoot = vfsStream::setup('root_dir', 0777, array(
      'logs' => array(),
    ));

    $fileWriter = $this->_newFileWriter('log', 'vfs://root_dir/logs', 'vfs://root_dir/archived_logs');
    $this->assertTrue(null === $filesystemRoot->getChild('logs')->getChild('log.txt'));
    $this->_($fileWriter, '_init');
    $this->assertTrue(file_exists($filesystemRoot->getChild('logs')->getChild('log.txt')->url()));
    
  }

  /**
   * 
   */
  public function test_init_CreatesArchiveDir() {
    $filesystemRoot = vfsStream::setup('root_dir', 0777, array());
    $fileWriter = $this->_newFileWriter('log', 'vfs://root_dir/logs', 'vfs://root_dir/archived_logs');

    $this->assertTrue(null === $filesystemRoot->getChild('archived_logs'));
    $this->_($fileWriter, '_init');
    $this->assertTrue(file_exists($filesystemRoot->getChild('archived_logs')->url()));
  }
  
  /**
   * @dataProvider provider_init_HandlesLogArchivingCorrectly
   */
  public function test_init_HandlesLogArchivingCorrectly($mustMoveToArchive, $matcher) {
    $filesystemRoot = vfsStream::setup('root_dir', 0777, array());

    $fileWriterMock = $this->getMockBuilder(\Securetrading\Log\FileWriter::class)
        ->setMethods(['_mustMoveToArchive', '_moveToArchive'])
        ->setConstructorArgs(['log', 'vfs://root_dir/logs', 'vfs://root_dir/archived_logs'])
        ->getMock();
    
    $fileWriterMock
      ->expects($this->once())
      ->method('_mustMoveToArchive')
      ->with($this->equalTo('vfs://root_dir/logs/log.txt'))
      ->willReturn($mustMoveToArchive)
    ;
    
    $fileWriterMock
      ->expects($matcher)
      ->method('_moveToArchive')
    ;
    
    $this->_($fileWriterMock, '_init');
  }

  public function provider_init_HandlesLogArchivingCorrectly() {
    $this->_addDataSet(true, $this->once());
    $this->_addDataSet(false,  $this->never());
    return $this->_getDataSets();
  }

  /**
   * @dataProvider provider_mustMoveToArchive
   */
  public function test_mustMoveToArchive($content, $currentMonthAndYear, $logFileMonthAndYear, $expectedReturnValue) {
    $filesystemRoot = vfsStream::setup('root_dir', 0777, array(
      'logs' => array(),
      'archived_logs' => array()
    ));

    $fileWriter = $this->_newFileWriter('log', $filesystemRoot->getChild('logs')->url(), $filesystemRoot->getChild('archived_logs')->url());

    $mTime = 100;

    $file = vfsStream::newFile('log_file_name.txt', 777)
      ->setContent($content)
      ->lastModified($mTime)
      ->at($filesystemRoot->getChild('logs'))
    ;
    
    \Securetrading\Unittest\CoreMocker::mockCoreFunction('date', function($format, $time = null) use ($mTime, $currentMonthAndYear, $logFileMonthAndYear) {
	if ($time === $mTime) {
	  return $logFileMonthAndYear;
	}
	else {
	  return $currentMonthAndYear;
	}
    });

    $actualReturnValue = $this->_($fileWriter, '_mustMoveToArchive', $file->url());
    $this->assertEquals($expectedReturnValue, $actualReturnValue);
  }
  
  public function provider_mustMoveToArchive() {
    $this->_addDataSet('contents', '02_2015', '01_2015', true);
    $this->_addDataSet('', '02_2015', '01_2015', true);
    $this->_addDataSet('contents', '01_2015', '01_2015', false);
    $this->_addDataSet('', '01_2015', '01_2015', false);
    return $this->_getDataSets();
  }
  
  /**
   *
   */
  public function test_moveToArchive() {
    $rootDir = vfsStream::setup('root_dir', 0777, array(
      'logs' => array(),
      'archived_logs' => array()
    ));

    $logFileContents = 'log file contents';

    $file = vfsStream::newFile('log.txt', 0777)
      ->setContent($logFileContents)
      ->at($rootDir->getChild('logs'))
    ;

    \Securetrading\Unittest\CoreMocker::mockCoreFunction('date', '01_2015');

    $logFilePath = $file->url();
    $archiveFilePathThatWillBeCreated = $rootDir->getChild('archived_logs')->url() . DIRECTORY_SEPARATOR . 'log_01_2015.txt';

    $this->assertFalse(file_exists($archiveFilePathThatWillBeCreated));
    $this->assertEquals($logFileContents, file_get_contents($logFilePath));

    $fileWriter = $this->_newFileWriter('log', $rootDir->getChild('logs')->url(), $rootDir->getChild('archived_logs')->url());
    $this->_($fileWriter, '_moveToArchive');

    $this->assertTrue(file_exists($archiveFilePathThatWillBeCreated));
    $this->assertEquals('', file_get_contents($logFilePath));
    $this->assertEquals($logFileContents, file_get_contents($archiveFilePathThatWillBeCreated));
  }

  /**
   * 
   */
  public function test_moveToArchive_ArchiveFileNameAlreadyExists() {
    $this->expectException(\Securetrading\Log\FileWriterException::class);
    $this->expectExceptionCode(\Securetrading\Log\FileWriterException::CODE_ARCHIVE_FILE_ALREADY_EXISTS);
    
    $rootDir = vfsStream::setup('root_dir', 0777, array(
      'logs' => array(),
      'archived_logs' => array()
    ));

    $file = vfsStream::newFile('log.txt', 0777)
      ->setContent('contents')
      ->at($rootDir->getChild('logs'))
    ;
    
    \Securetrading\Unittest\CoreMocker::mockCoreFunction('date', '01_2015');

    $logFilePath = $file->url();
    $archiveFilePathThatWillBeCreated = $rootDir->getChild('archived_logs')->url() . DIRECTORY_SEPARATOR . 'log_01_2015.txt';

    $this->assertFalse(file_exists($archiveFilePathThatWillBeCreated));
    file_put_contents($archiveFilePathThatWillBeCreated, 'dummy contents');
    $this->assertTrue(file_exists($archiveFilePathThatWillBeCreated));

    $fileWriter = $this->_newFileWriter('log', $rootDir->getChild('logs')->url(), $rootDir->getChild('archived_logs')->url());
    $this->_($fileWriter, '_moveToArchive');
  }

  /**
   * @dataProvider providerGetLogDir
   */
  public function testGetLogDir($logDirToSet, $expectedReturnValue) {
    $fileWriter = $this->_newFileWriter('log', $logDirToSet, 'logs/archived_logs');
    $this->assertEquals($expectedReturnValue, $fileWriter->getLogDir());
  }

  public function providerGetLogDir() {
    $this->_addDataSet('log/dir', 'log/dir/');
    $this->_addDataSet('log/dir/', 'log/dir/');
    return $this->_getDataSets();
  }

  /**
   * @dataProvider providerGetArchiveDir
   */
  public function testGetArchiveDir($archiveDirToSet, $expectedReturnValue) {
    $fileWriter = $this->_newFileWriter('log', 'log_dir', $archiveDirToSet);
    $this->assertEquals($expectedReturnValue, $fileWriter->getArchiveDir());
  }

  public function providerGetArchiveDir() {
    $this->_addDataSet('archive/dir', 'archive/dir/');
    $this->_addDataSet('archive/dir/', 'archive/dir/');
    return $this->_getDataSets();
  }

  /**
   * 
   */
  public function testGetLogFilepath() {
    $fileWriter = $this->_newFileWriter('log', 'log_dir', 'archive_dir');
    $this->assertEquals('log_dir/log.txt', $fileWriter->getLogFilepath());
  }

  /**
   *
   */
  public function testGetArchiveFilepath() {
    $fileWriter = $this->_newFileWriter('log', 'log_dir', 'archive_dir');
    $this->assertEquals('archive_dir/log_01_2000.txt', $fileWriter->getArchiveFilepath('01_2000'));
  }

  /**
   * 
   */
  public function testLog() {
    \Securetrading\Unittest\CoreMocker::mockCoreFunction('date', '01_2015');

    $rootDir = vfsStream::setup('root_dir', 0777, array(
      'logs' => array(),
      'archived_logs' => array()
    ));

    $logFilePath = $rootDir->getChild('logs')->url() . '/log.txt';

    $this->assertFalse(file_exists($logFilePath));

    $fileWriter = $this->_newFileWriter('log', $rootDir->getChild('logs')->url(), $rootDir->getChild('archived_logs')->url());
    $fileWriter->log(null, 'test message 1');
    $fileWriter->log(null, 'test message 2');

    $expectedContents = "test message 1" . PHP_EOL . "test message 2" . PHP_EOL;
    $actualContents = file_get_contents($logFilePath);
    $this->assertEquals($expectedContents, $actualContents);
  }

  /**
   *
   */
  public function testLog_CallsInit() {
    $filesystemRoot = vfsStream::setup('root_dir', 0777, array());

    $fileWriterMock = $this->getMockBuilder(\Securetrading\Log\FileWriter::class)
        ->setMethods(['_init'])
        ->setConstructorArgs(['log', 'vfs://root_dir/logs', 'vfs://root_dir/archived_logs'])
        ->getMock();
	
    mkdir('vfs://root_dir/logs');
    
    $fileWriterMock
      ->expects($this->once())
      ->method('_init')
    ;

    @$fileWriterMock->log(null, 'message');
  }
}