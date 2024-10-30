<?php

session_start();

use PierreMiniggioManual\TiktokToShorts\App;

require __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

(new App($_SERVER['HTTP_HOST']))->run();
