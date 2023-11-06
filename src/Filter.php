<?php

namespace Securetrading\Log;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class Filter extends AbstractLogger {
  const EMERGENCY = 1;
  const ALERT = 2;
  const CRITICAL = 4;
  const ERROR = 8;
  const WARNING = 16;
  const NOTICE = 32;
  const INFO = 64;
  const DEBUG = 128;

  protected $_map = array(
    LogLevel::EMERGENCY => self::EMERGENCY,
    LogLevel::ALERT => self::ALERT,
    LogLevel::CRITICAL => self::CRITICAL,
    LogLevel::ERROR => self::ERROR,
    LogLevel::WARNING => self::WARNING,
    LogLevel::NOTICE => self::NOTICE,
    LogLevel::INFO => self::INFO,
    LogLevel::DEBUG => self::DEBUG,
  );

  protected $_logLevel;

  protected $_logger;

  public function __construct() {
    $this->_logLevel = self::EMERGENCY | self::ALERT | self::CRITICAL | self::ERROR | self::WARNING | self::NOTICE | self::INFO | self::DEBUG;
  }

  public function setLogger(LoggerInterface $logger) {
    $this->_logger = $logger;
    return $this;
  }

  public function getLogger() {
    if ($this->_logger === null) {
      throw new FilterException('The log writer has not been set.', FilterException::CODE_LOGGER_NOT_SET);
    }
    return $this->_logger;
  }

  public function setLogLevel($logLevel) {
    $this->_logLevel = $logLevel;
    return $this;
  }
  
  public function getLogLevel() {
    return $this->_logLevel;
  }

  public function log($logLevel, $message, array $context = array()): void {
    if ($this->_canLog($logLevel)) {
      $this->getLogger()->log($logLevel, $message, $context = array());
    }
    return;
  }
  
  protected function _canLog($logLevel) {
    return (bool) ($this->_getLogLevelNumber($logLevel) & $this->getLogLevel());
  }

  protected function _getLogLevelNumber($logLevel) {
    if (!array_key_exists($logLevel, $this->_map)) {
      throw new FilterException(sprintf('Log level "%s" not recognised.', $logLevel), FilterException::CODE_INVALID_LOG_LEVEL);
    }
    return $this->_map[$logLevel];
  }
}