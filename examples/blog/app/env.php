<?php
/**
    Setup paths
**/
define('APP_PATH', 		realpath(BASE_PATH.'/application'));
define('CACHE_PATH', 	realpath(APP_PATH.'/Cache'));
define('LOG_PATH', 		realpath(APP_PATH.'/Logs'));
define('TPL_PATH', 		realpath(APP_PATH.'/Templates'));
define('TMP_PATH',		realpath(BASE_PATH.'/tmp'));
define('BIN_PATH',    	realpath(BASE_PATH.'/bin'));
define('LIB_PATH',      realpath(BASE_PATH.'/lib'));
define('STATIC_PATH', 	realpath(BASE_PATH.'/static'));