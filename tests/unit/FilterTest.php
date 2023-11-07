<?php

namespace Securetrading\Log\Tests\Unit;

use \Securetrading\Log\Filter;
use \Psr\Log\LogLevel;

class FilterTest extends \Securetrading\Unittest\UnittestAbstract {
  protected $_filter;

  protected function getAbstractLoggerMock() {
    return $this->getMockForAbstractClass('\Psr\Log\AbstractLogger');
  }

  public function setUp() : void {
    $this->_filter = new Filter();
  }

  /**
   *
   */
  public function testSetLogger() {
    $mockLogger = $this->getAbstractLoggerMock();
    $returnValue = $this->_filter->setLogger($mockLogger);
    $this->assertSame($this->_filter, $returnValue);
  }

  /**
   * 
   */
  public function testGetLogger_IfNotSet() {
    $this->expectException(\Securetrading\Log\FilterException::class);
    $this->expectExceptionCode(\Securetrading\Log\FilterException::CODE_LOGGER_NOT_SET);
    
    $this->_filter->getLogger();
  }

  /**
   * 
   */
  public function testGetLogger_IfSet() {
    $mockLogger = $this->getAbstractLoggerMock();
    $this->_filter->setLogger($mockLogger);
    $returnValue = $this->_filter->getLogger();
    $this->assertSame($mockLogger, $returnValue);
  }

  /**
   *
   */
  public function testSetLogLevel() {
    $returnValue = $this->_filter->setLogLevel(Filter::EMERGENCY);
    $this->assertSame($this->_filter, $returnValue);
  }

  /**
   * 
   */
  public function testGetLogLevel_DefaultValue() {
    $expectedReturnValue = Filter::EMERGENCY | Filter::ALERT | Filter::CRITICAL | Filter::ERROR | Filter::WARNING | Filter::NOTICE | Filter::INFO | Filter::DEBUG;
    $actualReturnValue = $this->_filter->getLogLevel();
    $this->assertEquals($expectedReturnValue, $actualReturnValue);
  }

  /**
   * 
   */
  public function testGetLogLevel_AfterSetting() {
    $expectedReturnValue = Filter::EMERGENCY | Filter::ALERT;
    $this->_filter->setLogLevel($expectedReturnValue);
    $actualReturnValue = $this->_filter->getLogLevel();
    $this->assertEquals($expectedReturnValue, $actualReturnValue);
  }

  /**
   * @dataProvider providerLog_WhenCanLog
   */
  public function testLog_WhenCanLog($logLevelToSet, $logLevel) {
//     $logLevel = null;
    $message = 'my message';
    $context = array();

    $mockLogger = $this->getAbstractLoggerMock();
    $mockLogger
      ->expects($this->once())
      ->method('log')
      ->with(
        $this->equalTo($logLevel),
	$this->equalTo($message),
	$this->equalTo($context)
      )
    ;
    $this->_filter->setLogger($mockLogger);

    $this->_filter->setLogLevel($logLevelToSet);

    $returnValue = $this->_filter->log($logLevel, $message, $context);
    $this->assertNotSame($this->_filter, $returnValue);
  }

  public function providerLog_WhenCanLog() {
    $this->_addDataSet(Filter::EMERGENCY, LogLevel::EMERGENCY);
    $this->_addDataSet(Filter::EMERGENCY | Filter::WARNING, LogLevel::WARNING);
    return $this->_getDataSets();
  }

  /**
   * @dataProvider providerLog_WhenCannotLog
   */
  public function testLog_WhenCannotLog($logLevelToSet, $logLevel) {
    $mockLogger = $this->getAbstractLoggerMock();
    $mockLogger
      ->expects($this->never())
      ->method('log')
    ;
    $this->_filter->setLogger($mockLogger);

    $this->_filter->setLogLevel($logLevelToSet);

    $returnValue = $this->_filter->log($logLevel, 'my message');
    $this->assertNotSame($this->_filter, $returnValue);
  }

  public function providerLog_WhenCannotLog() {
    $this->_addDataSet(Filter::EMERGENCY, LogLevel::WARNING);
    $this->_addDataSet(Filter::EMERGENCY | Filter::WARNING, LogLevel::DEBUG);
    return $this->_getDataSets();
  }

  /**
   * @dataProvider provider_canLog
   */
  public function test_canLog($logLevelToSet, $logLevelToTest, $expectedReturnValue) {
    $this->_filter->setLogLevel($logLevelToSet);
    $actualReturnValue = $this->_($this->_filter, '_canLog', $logLevelToTest);
    $this->assertEquals($expectedReturnValue, $actualReturnValue);
  }

  public function provider_canLog() {
    $this->_addDataSet(Filter::EMERGENCY, LogLevel::ALERT, false);
    $this->_addDataSet(Filter::EMERGENCY, LogLevel::EMERGENCY, true);
    $this->_addDataSet(Filter::EMERGENCY | Filter::DEBUG, LogLevel::INFO, false);
    $this->_addDataSet(Filter::EMERGENCY | Filter::DEBUG, LogLevel::DEBUG, true);
    return $this->_getDataSets();
  }

  /**
   * @dataProvider provider_getLogLevelNumber_WithValidLogLevels
   */
  public function test_getLogLevelNumber_WithValidLogLevels($logLevel, $expectedReturnValue) {
    $actualReturnValue = $this->_($this->_filter, '_getLogLevelNumber', $logLevel);
    $this->assertEquals($expectedReturnValue, $actualReturnValue);
  }

  public function provider_getLogLevelNumber_WithValidLogLevels() {
    $this->_addDataSet(LogLevel::EMERGENCY, Filter::EMERGENCY);
    $this->_addDataSet(LogLevel::ALERT, Filter::ALERT);
    $this->_addDataSet(LogLevel::CRITICAL, Filter::CRITICAL);
    $this->_addDataSet(LogLevel::ERROR, Filter::ERROR);
    $this->_addDataSet(LogLevel::WARNING, Filter::WARNING);
    $this->_addDataSet(LogLevel::NOTICE, Filter::NOTICE);
    $this->_addDataSet(LogLevel::INFO, Filter::INFO);
    $this->_addDataSet(LogLevel::DEBUG, Filter::DEBUG);
    return $this->_getDataSets();
  }

  /**
   * 
   */
  public function test_getLogLevelNumber_WithInvalidLogLevel() {
    $this->expectException(\Securetrading\Log\FilterException::class);
    $this->expectExceptionCode(\Securetrading\Log\FilterException::CODE_INVALID_LOG_LEVEL);
    
    $this->_($this->_filter, '_getLogLevelNumber', 'INVALID_LOG_LEVEL_STRING');
  }
}