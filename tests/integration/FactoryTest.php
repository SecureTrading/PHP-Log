<?php

namespace Securetrading\Log\Tests\Integration;

use \Securetrading\Log\Filter as Filter;

class FactoryTest extends \Securetrading\Unittest\IntegrationtestAbstract {
  public function setUp() {
    parent::setUp();
    $this->_ioc = \Securetrading\Ioc\Helper::instance()
      ->loadPackage('stLog', \Securetrading\Loader\Loader::getRootPath())
      ->getIoc()
    ;
  }

  protected function _assertLogTimestamp($logFileContents, $expectedLines) {
    $seenCount = 0;
    foreach(explode("\n", $logFileContents) as $logLine) {
      if (empty($logLine)) {
	continue;
      }
      $this->assertEquals(1, preg_match('/^\d{2}-\d{2}-\d{4} \d{2}:\d{2}:\d{2} \w{3}.+$/', $logLine));
      $seenCount++;
    }
    $this->assertEquals($expectedLines, $seenCount);
  }

  protected function _stripTimestampFromLog($logFileContents) {
    return preg_replace('/^\d{2}-\d{2}-\d{4} \d{2}:\d{2}:\d{2} \w{3} - /m', '', $logFileContents);
  }

  /**
   * 
   */
  public function testLogFileWriter() {
    $fileWriter = $this->_ioc->get('\Securetrading\Log\FileWriter');

    $this->assertEquals(\Securetrading\Loader\Loader::getLogPath() . 'log.txt', $fileWriter->getLogFilePath());
    $this->assertEquals(\Securetrading\Loader\Loader::getLogArchivePath(), $fileWriter->getArchiveDir());

    $fileWriter = $this->_ioc->get('\Securetrading\Log\FileWriter', array(
      'logFileName' => 'test_log',
      'logFilePath' => $this->_testDir . 'test_logs',
      'logArchivePath' => $this->_testDir . 'test_archive_logs',
    ));

    $this->assertEquals($this->_testDir . 'test_logs/test_log.txt', $fileWriter->getLogFilePath());
    $this->assertEquals($this->_testDir . 'test_archive_logs/', $fileWriter->getArchiveDir());
  }

  /**
   *
   */
  public function testLogFilter() {
    $filter = $this->_ioc->get('\Securetrading\Log\Filter');
    $this->assertEquals(
      Filter::EMERGENCY | Filter::ALERT | Filter::CRITICAL | Filter::ERROR | Filter::WARNING | Filter::NOTICE | Filter::INFO | Filter::DEBUG,
      $filter->getLogLevel()
    );

    $filter = $this->_ioc->get('\Securetrading\Log\Filter', array('logLevel' => \Securetrading\Log\Filter::ALERT));
    $this->assertEquals(
      Filter::ALERT,
      $filter->getLogLevel()
    );
  }

  /**
   *
   */
  public function testStLog() {
    $log = $this->_ioc->get('stLog', array(
      'logFileName' => 'test_log',
      'logFilePath' => $this->_testDir . 'test_logs/',
      'logArchivePath' => $this->_testDir . 'test_archive_logs/',
    ));

    $log->emergency('Emergency message.');
    $log->alert('Alert message.');
    $log->critical('Critical message.');
    $log->error('Error message.');
    $log->warning('Warning message.');
    $log->notice('Notice message.');
    $log->info('Info message.');
    $log->debug('Debug message one.');
    $log->log(\Psr\Log\LogLevel::DEBUG, 'Debug message two.');

    $logFilePath = $this->_testDir . '/test_logs/test_log.txt';
    $logFileContents = file_get_contents($logFilePath);

    $this->_assertLogTimestamp($logFileContents, 9);

    $actualContents = $this->_stripTimestampFromLog($logFileContents);
    $expectedContents = <<<EXPECTED
EMERGENCY - Emergency message.
ALERT - Alert message.
CRITICAL - Critical message.
ERROR - Error message.
WARNING - Warning message.
NOTICE - Notice message.
INFO - Info message.
DEBUG - Debug message one.
DEBUG - Debug message two.

EXPECTED;
    $this->assertEquals($expectedContents, $actualContents);
  }

  public function testStLog_UsingLogLevel() {
    $log = $this->_ioc->get('stLog', array(
      'logFileName' => 'test_log',
      'logFilePath' => $this->_testDir . '/test_logs/',
      'logArchivePath' => $this->_testDir . '/test_archive_logs/',
      'logLevel' => \Securetrading\Log\Filter::EMERGENCY | \Securetrading\Log\Filter::ALERT,
    ));
    
    $log->emergency('Emergency one.');
    $log->debug('Debug one.');
    $log->alert('Alert one.');
    
    $logFilePath = $this->_testDir . '/test_logs/test_log.txt';
    $logFileContents = file_get_contents($logFilePath);
    $actualContents = $this->_stripTimestampFromLog($logFileContents);
    $expectedContents = <<<EXPECTED
EMERGENCY - Emergency one.
ALERT - Alert one.

EXPECTED;
    $this->assertEquals($expectedContents, $actualContents);
  }
}