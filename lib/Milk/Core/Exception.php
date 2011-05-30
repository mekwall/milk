<?php
namespace Milk\Core;

use \Exception as PHPException,
	\ErrorException as PHPErrorException;

/**
	Interface for exceptions
**/
interface IException {	
	// Exception message
    public function getMessage();
	// User-defined Exception code	
    public function getCode();
	// Source filename
    public function getFile();
	// Source line
    public function getLine();
	// An array of the backtrace()
    public function getTrace();
	// Formated string of trace
    public function getTraceAsString();
	// Formatted string for display
    public function __toString();
	// Constructor
    public function __construct($message = null, $code = 0);
}

/**
	Base exception
**/
class Exception extends PHPException implements IException {

	public static function exceptionHandler($e) {
		// Message templates
		$traceline_tpl = "#%s %s(%s): %s(%s)";
		$traceline_tpl_class = "#%s %s%s%s(%s)";
		$msg_tpl = "PHP Fatal error:  Uncaught exception '%s' with message '%s' in %s:%s\n\nStack trace:\n%s\n  thrown in %s on line %s";
		
		// Extract vars from exception
		$message = $e->getMessage();
		$file = $e->getFile();
		$line = $e->getLine();
		$trace = $e->getTrace();
		$class = get_class($e);

		// Alter trace
		foreach ($trace as $key => $stackPoint) {
			// Convert arguments to their type (prevents passwords from ever 
			// getting logged as anything other than 'string')
			$trace[$key]['args'] = array_map('gettype', $trace[$key]['args']);
		}
		
		// Build tracelines
		$result = array();
		foreach ($trace as $key => $stackPoint) {
			$result[] = sprintf(
				isset($stackPoint['file']) ? $traceline_tpl : $traceline_tpl_class,
				$key,
				isset($stackPoint['file']) ? $stackPoint['file'] : $stackPoint['class'],
				isset($stackPoint['line']) ? $stackPoint['line'] : $stackPoint['type'],
				$stackPoint['function'],
				implode(', ', $stackPoint['args'])
			);
		}
		// trace always ends with {main}
		$result[] = '#' . ++$key . ' {main}';

		// Build message
		$msg = sprintf(
			$msg_tpl,
			$class,
			$message,
			$file,
			$line,
			implode("\n", $result),
			$file,
			$line
		);
		
		// Log message
		//error_log($msg);
		
		header("Status: 500");
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<link href="//alexgorbatchev.com/pub/sh/current/styles/shCore.css" rel="stylesheet" type="text/css">
	<link href="//alexgorbatchev.com/pub/sh/current/styles/shThemeDefault.css" rel="stylesheet" type="text/css">
	<link href="//ajax.googleapis.com/ajax/libs/jqueryui/1.8.13/themes/blitzer/jquery-ui.css" rel="stylesheet" type="text/css">
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js"></script>
	<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.8.13/jquery-ui.min.js"></script>
	<script src="//alexgorbatchev.com/pub/sh/current/scripts/shCore.js"></script>
	<script src="//alexgorbatchev.com/pub/sh/current/scripts/shBrushPhp.js"></script>
	<script>
		SyntaxHighlighter.config.clipboardSwf = "http://alexgorbatchev.com/pub/sh/current/scripts/clipboard.swf";
		SyntaxHighlighter.all();
		$(function(){
			$("#accordion").accordion({
				autoHeight: false,
				navigation: true,
				collapsible: true
			});
			SyntaxHighlighter.highlight();
			var hl = $("div.syntaxhighlighter");
			var fontSize = parseInt(hl.css("fontSize"))+1;
			console.log(fontSize);
			hl.height(fontSize*9)
			  .scrollTop(fontSize*<?php echo $line-5; ?>);
		});
	</script>
	<style>
		body, html { font-family: Trebuchet, Tahoma; font-size: .95em; }
		.ui-widget { font-size: .9em; }
		h2,h3,h4,h5,h6 { font-family: Arial, Serif; }
		p { line-height: 1.7em; }
		#container { margin: auto; max-width: 1000px; }
		#footer { text-align: center; margin: 20px; line-height: 1.5em; }
		pre,
		span.code,
		.syntaxhighlighter {
			font-family: Consolas;
		}
		.syntaxhighlighter .line.highlighted.alt1,
		.syntaxhighlighter .line.highlighted.alt2 {
			background-color: #ffcece !important;
		}

		.syntaxhighlighter .gutter .line.highlighted {
			background-color: red !important;
			border-right: 3px solid red !important;
		}
		span.code { display: inline; background-color: #f1f1f1; padding: 2px 5px; } 
	</style>
	<title>Internal Server Error</title>
</head>
<body>
<div id="container">
	<h1>Internal Server Error</h1>
	<h3>Fatal error: Uncaught exception '<?php echo $class; ?>'</h3>
	<p><?php echo $message ?> in file <span class="code"><?php echo $file ?></span> on line <?php echo $line ?></p>
	<div id="accordion">
		<h3><a href="#">Source</a></h3>

		<div>
			<pre style="display: none;" class="brush: php; highlight: [<?php echo $line; ?>];"><?php echo htmlentities(file_get_contents($file)); ?></pre>
		</div>

		<h3><a href="#">Stack trace</a></h3>
		<div>
			<pre><?php echo implode("\n", $result); ?></pre>
		</div>
		
		<h3><a href="#">$_SERVER dump</a></h3>
		<div>
			<pre><?php var_dump($_SERVER); ?></pre>
		</div>
		
		<h3><a href="#">Response headers</a></h3>
		<div>
			<pre><?php var_dump(\xdebug_get_headers()); ?></pre>
		</div>
	</div>
	<div id="footer">
		<img src="http://27.media.tumblr.com/tumblr_lh1jy8q1rO1qdmdxco1_500.jpg" alt="Milk was a bad choice!" /><br>
		PHP <?php echo phpversion(); ?> • <?php echo PHP_SAPI; ?> • <?php echo $_SERVER['SERVER_SOFTWARE']; ?> • <?php echo PHP_OS; ?> • <?php echo $_SERVER['SERVER_NAME']; ?><br>
		Time: <?php echo round(xdebug_time_index(), 4); ?> sec • Curr/peak: <?php echo xdebug_memory_usage(); ?>/<?php echo xdebug_peak_memory_usage(); ?> Bytes
	</div>
</div>
</body>
</html>
<?php
	}
}

class ErrorException extends PHPErrorException {

	public static function errorHandler($code, $message, $file, $line) {
		throw new self($message, 0, $code, $file, $line);
	}
}

ini_set('xdebug.collect_params', '3');
set_exception_handler('Milk\Core\Exception::exceptionHandler');
set_error_handler('Milk\Core\ErrorException::errorHandler');