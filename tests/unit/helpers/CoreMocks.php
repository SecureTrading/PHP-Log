<?php

namespace Securetrading\Log;

function date($format, $timestamp = null) {
  return \Securetrading\Unittest\CoreMocker::runCoreMock('date', array($format, $timestamp));
}