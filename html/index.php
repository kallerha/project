<?php

ini_set('session.cookie_httponly', 'On');
ini_set('session.cookie_lifetime', '3600');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.cookie_secure', 'On');
ini_set('session.gc_maxlifetime', '3600');
ini_set('session.use_cookies', 'On');
ini_set('session.use_only_cookies', 'On');
ini_set('session.use_strict_mode', 'On');
ini_set('session.use_trans_sid', 'Off');

session_name('PHPSSID');

require __DIR__ . '/../bootstrap.php';