<?php

namespace Securetrading\Log;

class Factory {
  public static function logFileWriter(\Securetrading\Ioc\IocInterface $ioc, $alias, $params) {
    $fileName = $ioc->hasParameter('logFileName', $params) ? $ioc->getParameter('logFileName', $params) : 'log';
    $filePath = $ioc->hasParameter('logFilePath', $params) ? $ioc->getParameter('logFilePath', $params) : \Securetrading\Loader\Loader::getLogPath();
    $archivePath = $ioc->hasParameter('logArchivePath', $params) ? $ioc->getParameter('logArchivePath', $params) : \Securetrading\Loader\Loader::getLogArchivePath();
    
    return new FileWriter($fileName, $filePath, $archivePath);
  }

  public static function logFilter(\Securetrading\Ioc\IocInterface $ioc, $alias, $params) {
    $filter = new Filter();
    if ($ioc->hasParameter('logLevel', $params)) {
      $filter->setLogLevel($ioc->getParameter('logLevel', $params));
    }
    return $filter;
  }

  public static function log(\Securetrading\Ioc\IocInterface $ioc, $alias, $params) {
    $formatter = $ioc->get('\Securetrading\Log\Formatter');
    $filter = $ioc->get('\Securetrading\Log\Filter', $params);
    $fileWriter = $ioc->get('\Securetrading\Log\FileWriter', $params);

    $formatter->setLogger($filter);
    $filter->setLogger($fileWriter);

    return $formatter;
  }
}