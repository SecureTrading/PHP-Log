<?php

return array(
  'stLog' => array(
    'definitions' => array(
      '\Securetrading\Log\Filter' => array('\Securetrading\Log\Factory', 'logFilter'),
      '\Securetrading\Log\FileWriter' => array('\Securetrading\Log\Factory', 'logFileWriter'),
      'stLog' => array('\Securetrading\Log\Factory', 'log'),
    ),
  ),
);