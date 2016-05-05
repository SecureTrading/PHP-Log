<?php

namespace Securetrading\Log;

function date($format, $timestamp = null) {
  if ($timestamp === null) {
    $timestamp = time(); // Like the core date() function.
  }
  return \Securetrading\Unittest\CoreMocker::runCoreMock('date', array($format, $timestamp));
}