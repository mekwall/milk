<?php
namespace Milk\Utils;

use \F3,
	\Exception;

use \Milk\Utils\OS;

class WKPDF {

	//! Force the client to download PDF file when finish() is called
	const PDF_DOWNLOAD = 'D';
	
	//! Returns the PDF file as a string when finish() is called
	const PDF_ASSTRING = 'S';
	
	//! When possible, force the client to embed PDF file when finish() is called
	const PDF_EMBEDDED = 'I';
	
	//! PDF file is saved into the server space when finish() is called. The path is returned
	const PDF_SAVEFILE = 'F';
	
	//! PDF generated as landscape (vertical)
	const PDF_PORTRAIT = 'Portrait';
	
	//! PDF generated as landscape (horizontal)
	const PDF_LANDSCAPE = 'Landscape';

	//! Private vars
	private $html ='';
	private $cmd ='';
	private $tmp ='';
	private $pdf ='';
	private $status = '';
	private $orient = 'Portrait';
	private $size = 'A4';
	private $encoding = 'utf8';
	private $javascript = false;
	private $forms = false;
	private $toc = false;
	private $copies = 1;
	private $grayscale = false;
	private $title = '';
	private $stylesheet = '';

	//! Constructor: initialize command line and reserve temporary file
	public function __construct() {
	
			$this->cmd = BIN_PATH.'/wkhtmltopdf-'.( OS::is64() ? 'amd64' : 'i386' );
			
			if(!file_exists($this->cmd))
				throw new Exception('WKPDF static executable "'.htmlspecialchars($this->cmd,ENT_QUOTES).'" was not found.');
			
			do {
				$this->tmp=TMP_PATH.'/'.mt_rand().'.html';
			} while(file_exists($this->tmp));
	}
	
	/**
		Advanced execution routine
			@param string $cmd The command to execute
			@param string $input Any input not in arguments
			@return array An array of execution data; stdout, stderr and return "error" code
	**/
	private static function _pipeExec($cmd,$input='') {
		//! open proc pipes
		$proc = proc_open(
			$cmd,
			array(
				0 => array('pipe','r'),
				1 => array('pipe','w'),
				2 => array('pipe','w')
			),
			$pipes
		);
		
		//! write input to pipe #0
		fwrite($pipes[0], $input);
		fclose($pipes[0]);
		
		//! read output from pipe #1
		$stdout = stream_get_contents($pipes[1]);
		fclose($pipes[1]);
		
		//! read output from pipe #2
		$stderr = stream_get_contents($pipes[2]);
		fclose($pipes[2]);
		
		//! close proc pipes
		$rtn = proc_close($proc);
		
		//! return results
		return array(
			'stdout'=>$stdout,
			'stderr'=>$stderr,
			'return'=>$rtn
		);
	}
	
	/**
		Set orientation, use constants from this class.
		By default orientation is portrait.
			@param string $mode Use constants from this class.
	**/
	public function setOrientation($mode) {
		$this->orient = $mode;
		return $this;
	}
	
	/**
		Set page/paper size.
		By default page size is A4.
			@param string $size Formal paper size (eg; A4, letter...)
	**/
	public function setPageSize($size) {
		$this->size = $size;
		return $this;
	}
	
	/**
		Whether to automatically generate a TOC (table of contents) or not.
		By default TOC is disabled.
			@param boolean $enabled True use TOC, false disable TOC.
	**/
	public function setTOC($enabled) {
		$this->toc = $enabled;
		return $this;
	}
	
	/**
		Set the number of copies to be printed.
		By default it is one.
			@param integer $count Number of page copies.
	**/
	public function setCopies($count) {
		$this->copies = $count;
		return $this;
	}
	
	/**
		Whether to print in grayscale or not.
		By default it is OFF.
			@param boolean True to print in grayscale, false in full color.
	**/
	public function setGrayscale($mode) {
		$this->grayscale = $mode;
		return $this;
	}
	
	/**
		Set PDF title. If empty, HTML <title> of first document is used.
		By default it is empty.
			@param string Title text.
	**/
	public function setTitle($text) {
		$this->title = $text;
		return $this;
	}
	
	/**
		Set html content.
			@param string $html New html content. It *replaces* any previous content.
	**/
	public function setHtml($html) {
		$this->html = $html;
		file_put_contents($this->tmp, $html);
		return $this;
	}
	
	/**
		Set user stylesheet.
		By default there is no stylesheet
			@param string $url
	**/
	public function setStylesheet($url) {
		$this->stylesheet = $url;
		return $this;
	}
	
	/**
		Returns WKPDF print status.
			@return string WPDF print status.
	**/
	public function getStatus(){
		return $this->status;
	}
	
	/**
		Attempts to return the library's full help.
			@return string WKHTMLTOPDF HTML help.
	**/
	public function getHelp(){
		$tmp = self::_pipeExec('"'.$this->cmd.'" --extended-help');
		return $tmp['stdout'];
	}
	
	/**
		Convert HTML to PDF.
	**/
	public function render() {
		//! temporary html file
		$web = TMP_PATH.'/'.basename($this->tmp);
		
		//! execute bin
		$this->pdf = self::_pipeExec(
		
			//! command to execute
			'"'.$this->cmd.'"'
			
			//! number of copies
			.(($this->copies>1)?' --copies '.$this->copies:'')
			
			//! orientation
			.' --orientation '.$this->orient
			
			//! page size
			.' --page-size '.$this->size
			
			//! encoding
			.' --encoding '.$this->encoding
			
			//! stylesheet
			.($this->stylesheet?' --user-style-sheet '.$this->stylesheet:'')
			
			//! disable javascript
			.(!$this->javascript?' --disable-javascript':' --enable-javascript')
			
			//! disable forms
			.($this->forms?' --enable-forms':' --disable-forms')
			
			//! table of contents
			.($this->toc?' --toc':'')
			
			//! grayscale
			.($this->grayscale?' --grayscale':'')
			
			//! title
			.(($this->title!='')?' --title "'.$this->title.'"':'')
			
			//! url
			.' "'.$web.'" -'
		);
		
		if (strpos(strtolower($this->pdf['stderr']), 'error') !== false)
			throw new Exception('WKPDF system error: <pre>'.$this->pdf['stderr'].'</pre>');
			
		if ($this->pdf['stdout'] == '')
			throw new Exception(
				'WKPDF didn\'t return any data. <pre>'.
				$this->pdf['stderr'].
				'</pre>'
			);
			
		if ((int)$this->pdf['return'] > 1)
			throw new Exception(
				'WKPDF shell error, return code '.
				(int)$this->pdf['return']
			);
			
		$this->status = $this->pdf['stderr'];
		$this->pdf = $this->pdf['stdout'];
		unlink($this->tmp);
		
		return $this;
	}
	
	/**
		Return PDF with various options.
			@param string $mode How two output (constants from this same class).
			@param string $file The PDF's filename (the usage depends on $mode.
			@return string|boolean Depending on $mode, this may be success (boolean) or PDF (string).
	**/
	public function output($mode,$file) {
		switch($mode) {
			case self::PDF_DOWNLOAD:
				if(!headers_sent()){
					header('Content-Description: File Transfer');
					header('Cache-Control: public, must-revalidate, max-age=0'); // HTTP/1.1
					header('Pragma: public');
					header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
					header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
					// force download dialog
					header('Content-Type: application/force-download');
					header('Content-Type: application/octet-stream', false);
					header('Content-Type: application/download', false);
					header('Content-Type: application/pdf', false);
					// use the Content-Disposition header to supply a recommended filename
					header('Content-Disposition: attachment; filename="'.basename($file).'";');
					header('Content-Transfer-Encoding: binary');
					header('Content-Length: '.strlen($this->pdf));
					echo $this->pdf;
				} else {
					throw new Exception('WKPDF download headers were already sent.');
				}
			break;
				
			case self::PDF_ASSTRING:
				return $this->pdf;
			break;
			
			case self::PDF_EMBEDDED:
				if(!headers_sent()){
					header('Content-Type: application/pdf');
					header('Cache-Control: public, must-revalidate, max-age=0'); // HTTP/1.1
					header('Pragma: public');
					header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
					header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
					header('Content-Length: '.strlen($this->pdf));
					header('Content-Disposition: inline; filename="'.basename($file).'";');
					echo $this->pdf;
				} else {
					throw new Exception('WKPDF embed headers were already sent.');
				}
			break;
			
			case self::PDF_SAVEFILE:
				return file_put_contents($file,$this->pdf);
			break;
			
			default:
				throw new Exception('WKPDF invalid mode "'.htmlspecialchars($mode,ENT_QUOTES).'".');
		}
		return false;
	}
}