<?php

namespace Securetrading\Log\Tests\Unit;

use Securetrading\Unittest\CoreMocker;

require_once(__DIR__ . '/helpers/CoreMocks.php');

class FormatterTest extends \Securetrading\Unittest\UnittestAbstract {
  protected $_formatter;

  public function setUp() : void {
    $this->_formatter = new \Securetrading\Log\Formatter();
  }

  public function tearDown() : void {
    CoreMocker::releaseCoreMocks();
  }

  protected function getAbstractLoggerMock() {
    return $this->getMockForAbstractClass('\Psr\Log\AbstractLogger');
  }

  /**
   *
   */
  public function testSetLogger() {
    $mockLogger = $this->getAbstractLoggerMock();
    $returnValue = $this->_formatter->setLogger($mockLogger);
    $this->assertSame($this->_formatter, $returnValue);
  }

  /**
   *
   */
  public function testGetLogger_IfNotSet() {
    $this->expectException(\Securetrading\Log\FormatterException::class);
    $this->expectExceptionCode(\Securetrading\Log\FormatterException::CODE_LOGGER_NOT_SET);
    
    $this->_($this->_formatter, '_getLogger');
  }

  /**
   * 
   */
  public function testGetLogger_IfSet() {
    $mockLogger = $this->getAbstractLoggerMock();
    $this->_formatter->setLogger($mockLogger);
    $returnValue = $this->_($this->_formatter, '_getLogger');
    $this->assertSame($mockLogger, $returnValue);
  }

  /**
   * @dataProvider providerLog
   */
  public function testLog($logLevel, $message, array $context, $expectedMessagePassedToLogger) {
    CoreMocker::mockCoreFunction('date', 'currentdate');
    $mockLogger = $this->getAbstractLoggerMock();
    $mockLogger
      ->expects($this->once())
      ->method('log')
      ->with(
        $this->equalTo($logLevel),
	$this->equalTo($expectedMessagePassedToLogger),
	$this->equalTo($context)
      )
    ;
    $this->_formatter->setLogger($mockLogger);

    $returnValue = $this->_formatter->log($logLevel, $message, $context);
    $this->assertNotSame($this->_formatter, $returnValue);
  }

  public function providerLog() {
    $this->_addDataSet('myloglevel', 'my message with {c}.', array('c' => 'context'), 'currentdate - MYLOGLEVEL - my message with context.');
    return $this->_getDataSets();
  }

  /**
   * @dataProvider provider_formatMessage
   */
  public function test_formatMessage($logLevel, $message, array $context, $expectedReturnValue) {
    CoreMocker::mockCoreFunction('date', 'currentdate');
    $actualReturnValue = $this->_($this->_formatter, '_formatMessage', $logLevel, $message, $context);
    $this->assertEquals($expectedReturnValue, $actualReturnValue);
  }

  public function provider_formatMessage() {
    $this->_addDataSet('myloglevel', 'my message with {c}.', array('c' => 'context'), 'currentdate - MYLOGLEVEL - my message with context.');
    return $this->_getDataSets();
  }

  /**
   * @dataProvider provider_messageToString
   */
  public function test_messageToString($inputMessage, $expectedReturnValue) {
    $actualReturnValue = $this->_($this->_formatter, '_messageToString', $inputMessage);
    $this->assertTrue(1 === preg_match($expectedReturnValue, $actualReturnValue));
  }

  public function provider_messageToString() {
    $this->_addDataSet('string message', '/^string message$/');
    $this->_addDataSet(1, '/^1$/');
    $this->_addDataSet(12.34, '/^12.34$/');
    $this->_addDataSet(true, '/^1$/');
    $this->_addDataSet(
      array(1,2,3), 
      "/^array \(\n" . 
      "  0 => 1,\n" . 
      "  1 => 2,\n" . 
      "  2 => 3,\n" . 
      "\)$/"
    );
    $this->_addDataSet(
      new \stdClass(),
      "/^object\(stdClass\)#\d{3,} \(0\) {\n" . 
      "}$/"
      );
    return $this->_getDataSets();
  }

  /**
   * 
   */
  public function test_interpolateContext() {
    $message = 'Testing {i} into {s}.';
    $context = array(
      'i' => 'interpolation',
      's' => 'string',
    );

    $expectedReturnValue = 'Testing interpolation into string.';
    $actualReturnValue = $this->_($this->_formatter, '_interpolateContext', $message, $context);
    $this->assertEquals($expectedReturnValue, $actualReturnValue);
  }

  /**
   * 
   */
  public function test_prependLogLevel() {
    $expectedReturnValue = 'MYLOGLEVEL - test message';
    $actualReturnValue = $this->_($this->_formatter, '_prependLogLevel', 'test message', 'myloglevel');
    $this->assertEquals($expectedReturnValue, $actualReturnValue);
  }

  /**
   * 
   */
  public function test_prependDate() {
    CoreMocker::mockCoreFunction('date', '20-05-2015 15:30:21 GMT');
    $expectedReturnValue = '20-05-2015 15:30:21 GMT - test message';
    $actualReturnValue =  $this->_($this->_formatter, '_prependDate', 'test message');
    $this->assertEquals($expectedReturnValue, $actualReturnValue);
  }
}