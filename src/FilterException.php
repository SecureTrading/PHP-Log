<?php

namespace Securetrading\Log;

class FilterException extends \Securetrading\Exception {
  const CODE_LOGGER_NOT_SET = 1;
  const CODE_INVALID_LOG_LEVEL = 2;
}