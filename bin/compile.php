<?php
require "../lib/Milk/Core/CLI.php";
use Milk\Core\CLI,
	Milk\Core\CLI\Colors;

define('APC_CLEAR_CACHE',           true);
define('APC_COMPILE_RECURSIVELY',   true);
define('APC_COMPILE_DIR',          	realpath(__DIR__.'/../lib/Milk'));

function apc_compile_dir($root, $recursive=true){
    $compiled = true;
    switch ($recursive) {
        case true:
            foreach(glob($root.DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR) as $dir) {
                $compiled = $compiled && apc_compile_dir($dir, $recursive);
			}
        case false:
            foreach(glob($root.DIRECTORY_SEPARATOR.'*.php') as $file) {
				$fname = str_replace('/home/oddy/milk/lib/', '', $file);
				CLI::out("Compiling file '$fname' ", false);
				$compiled = $compiled && apc_compile_file($file);
				if ($compiled)
					CLI::outOk();
				else
					CLI::outError();
				CLI::eol();
			}
            break;
    }
    return $compiled;
}

if (function_exists('apc_compile_file')) {
	CLI::out("Milk Automatic Byte-code Compiler");
    CLI::line();
	if (APC_CLEAR_CACHE) {
		CLI::out("Clearing cache ", false);
		apc_clear_cache() ? CLI::outOk() : CLI::outError();
		CLI::eol();
	}
	apc_compile_dir(APC_COMPILE_DIR, APC_COMPILE_RECURSIVELY);
	CLI::line();
} else
    CLI::error("APC is not loaded and/or installed");