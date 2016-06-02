<?php

namespace Securetrading\Log;

use Psr\Log\AbstractLogger;

class FileWriter extends AbstractLogger {
  protected $_logFileName;

  protected $_logDirectory;

  protected $_logFilePath;
  
  protected $_archiveDirectory;

  public function __construct($logFileName, $logDirectory, $archiveDirectory = null) {
    $this->_logFileName = $logFileName;
    $this->_logDirectory = $this->_addTrailingDirSeparator($logDirectory);
    $this->_logFilePath = $this->_logDirectory . $this->_logFileName . '.txt';
    $archiveDirectory = $archiveDirectory ?: $this->_logDirectory . 'archive';
    $this->_archiveDirectory = $this->_addTrailingDirSeparator($archiveDirectory);
  }

  protected function _addTrailingDirSeparator($inputDir) {
    return $inputDir .= (substr($inputDir, -1, 1) === DIRECTORY_SEPARATOR ? '' : DIRECTORY_SEPARATOR);
  }

  protected function _init() {
    clearstatcache();

    if (!file_exists($this->_logDirectory)) {
      mkdir($this->_logDirectory, 0700, true);
    }
    
    if (!file_exists($this->_logFilePath)) {
      file_put_contents($this->_logFilePath, '');
      @chmod($this->_logFilePath, 0700); // Note - error suppressed due to issue with vfsStream and chmod causing unit tests to fail: https://github.com/mikey179/vfsStream/wiki/Filemode
    }
    
    if (!file_exists($this->_archiveDirectory)) {
      mkdir($this->_archiveDirectory);
    }
    
    if ($this->_mustMoveToArchive($this->_logFilePath)) {
      $this->_moveToArchive();
    }
  }
  
  protected function _mustMoveToArchive($filePath) {
    $mTime = filemtime($filePath);
    $currentMonthAndYear = date('m_Y');
    $logFileMonthAndYear = date('m_Y', $mTime);
    return $logFileMonthAndYear !== $currentMonthAndYear;
  }
  
  protected function _moveToArchive() {
    $fileName = basename($this->_logFilePath, '.txt');
    $mTime = filemtime($this->_logFilePath);
    $logFileMonthAndYear = date('m_Y', $mTime);
    $newFilePath = $this->_archiveDirectory . $fileName . '_' . $logFileMonthAndYear . '.txt';
    
    if (file_exists($newFilePath)) {
      throw new FileWriterException(sprintf('The file "%s" already exists.', $newFilePath), FileWriterException::CODE_ARCHIVE_FILE_ALREADY_EXISTS);
    }
    
    copy($this->_logFilePath, $newFilePath);
    @chmod($newFilePath, 0700); // Note - error suppressed due to issue with vfsStream and chmod causing unit tests to fail: https://github.com/mikey179/vfsStream/wiki/Filemode
    file_put_contents($this->_logFilePath, '');
  }

  public function getLogDir() {
    return $this->_logDirectory;
  }

  public function getArchiveDir() {
    return $this->_archiveDirectory;
  }

  public function getLogFilepath() {
    return $this->_logFilePath;
  }

  public function getArchiveFilepath($logMonthAndYear) {
    return $this->_archiveDirectory . $this->_logFileName . '_' . $logMonthAndYear . '.txt';
  }

  public function log($logLevel, $message, array $context = array()) {
    $this->_init();
    $file = fopen($this->_logFilePath, 'a');
    fwrite($file, $message . PHP_EOL);
    fclose($file);
    return $this;
  }
}
