<?php

namespace Securetrading\Log;

class Formatter extends \Psr\Log\AbstractLogger {
  protected $_logger;

  public function setLogger(\Psr\Log\LoggerInterface $logger) {
    $this->_logger = $logger;
    return $this;
  }

  protected function _getLogger() {
    if ($this->_logger === null) {
      throw new FormatterException('The logger has not been set.', FormatterException::CODE_LOGGER_NOT_SET);
    }
    return $this->_logger;
  }

  public function log($logLevel, $message, array $context = array()) {
    $message = $this->_formatMessage($logLevel, $message, $context);
    $this->_getLogger()->log($logLevel, $message, $context);
    return $this;
  }

  protected function _formatMessage($logLevel, $message, array $context) {
    $message = $this->_messageToString($message);
    $message = $this->_interpolateContext($message, $context);
    $message = $this->_prependLogLevel($message, $logLevel);
    $message = $this->_prependDate($message);
    return $message;
  }

  protected function _messageToString($message) {
    if (is_object($message)) {
      if (method_exists($message, '__toString')) {
	$message = (string) $message;
      }
      else {
	ob_start();
	var_dump($message);
	$message = ob_get_clean();
	
      }
    }
    else if (is_array($message)) {
      $message = var_export($message, true);
    }
    else {
      $message = (string) $message;
    }
    return trim($message);
  }
  
  protected function _interpolateContext($message, array $context) {
    $replace = array();
    foreach ($context as $key => $value) {
      $replace['{' . $key . '}'] = $value;
    }
    return strtr($message, $replace);
  }

  protected function _prependLogLevel($message, $logLevel) {
    return strtoupper($logLevel) . ' - ' . $message;
  }

  protected function _prependDate($message) {
    return date('d-m-Y H:i:s T') . ' - ' . $message;
  }
}