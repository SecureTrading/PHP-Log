<?php

namespace Securetrading\Stpp\JsonInterface\Tests\Integration;

class FileWriterTest extends \Securetrading\Unittest\IntegrationtestAbstract {
  protected function _getFilePermissions($filePath) {
    return substr(sprintf('%o', fileperms($filePath)), -4);
  }

  public function testLog() {
    $fileWriter = new \Securetrading\Log\FileWriter('test_log_filename', $this->_testDir . 'logs/', $this->_testDir . 'archived_logs/');
    $fileWriter->log(null, 'message 1');
    $fileWriter->log(null, 'message 2');
    $this->assertEquals('message 1' . PHP_EOL . 'message 2' . PHP_EOL, file_get_contents($fileWriter->getLogFilePath()));

    $this->assertEquals('0700', $this->_getFilePermissions($fileWriter->getLogDir()));
    $this->assertEquals('0700', $this->_getFilePermissions($fileWriter->getLogFilePath()));
  }

  public function testLog_Archiving() {
    $fileWriter = new \Securetrading\Log\FileWriter('test_log_filename', $this->_testDir . 'logs/', $this->_testDir . 'archived_logs/');
    $logFilePath = $fileWriter->getLogFilePath();
    $archiveFilePath = $fileWriter->getArchiveFilepath('01_2000');

    $fileWriter->log(null, 'message 1');
    $fileWriter->log(null, 'message 2');

    $this->assertEquals('message 1' . PHP_EOL . 'message 2' . PHP_EOL, file_get_contents($logFilePath));

    $date = new \DateTime('2000-01-20');
    $timestamp = $date->getTimestamp();
    touch($logFilePath, $timestamp);
    clearstatcache();

    $fileWriter->log(null, 'message 3');
    
    $this->assertEquals('message 1' . PHP_EOL . 'message 2' . PHP_EOL, file_get_contents($archiveFilePath));
    $this->assertEquals('message 3' . PHP_EOL, file_get_contents($logFilePath));

    $this->assertEquals('0700', $this->_getFilePermissions($logFilePath));
    $this->assertEquals('0700', $this->_getFilePermissions($archiveFilePath));
  }
}