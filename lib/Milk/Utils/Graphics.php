<?php
namespace Milk\Utils;

class Graphics {

	public static function resample($image, $q=90, $w=null, $h=null, $to_type=null){
		if (!file_exists($image))
			return false;
	
		//! Get image size, type and attributes
		list($width, $height, $type, $attr) = getimagesize($image);
		
		//! If image is invalid
		if ($type == null || $width == null || $height == null)
			return false;
	
		//! Check if we are resizing
		if (!$w)
			$w = $width;
		if (!$h)
			$h = $height;
			
		//! Calculate ratio
		$ratio = $width/$height;
		
		if ($w > $h)
			$h = $w/$ratio;
		else
			$w = $h/$ratio;
			
		if (!$to_type)
			$to_type = $type;

		$target = imagecreatetruecolor($w, $h);
		
		switch ($type) {
			case IMAGETYPE_JPEG:
				$source = imagecreatefromjpeg($image);
			break;
			
			case IMAGETYPE_PNG:
				$source = imagecreatefrompng($image);
			break;
			
			case IMAGETYPE_GIF:
				$source = imagecreatefromgif($image);
			break;
		}
		
		//! Copy source image to target, and resize if necessary
		imagecopyresampled(
			$target,
			$source,
			0,
			0,
			0,
			0,
			$w,
			$h,
			$width,
			$height
		);
		
		switch ($to_type) {
			case IMAGETYPE_JPEG:
				imagejpeg($target, $image, $q);
			break;

			case IMAGETYPE_PNG:
				imagepng($target, $image, $q, PNG_ALL_FILTERS);
			break;
			
			case IMAGETYPE_GIF:
				imagegif($target, $image);
			break;
		}
		
		imagedestroy($target);
		imagedestroy($source);
	}
}

namespace Milk\Utils\Graphics;

use \StdClass;

class SpriteGen {

	const REGEX_NAME		= '/sprite:\s*(.*);/i';
	const REGEX_IMAGE		= '/sprite-image:\s*url\((.*)\);/i';
	const REGEX_LAYOUT		= '/sprite-layout:\s*(.*);/i';
	const REGEX_MARGIN		= '/sprite-margin:\s*(.*);/i';
	const REGEX_BG			= '/sprite-background:\s*(.*);/i';
	const REGEX_COLORS		= '/sprite-colorcount:\s*(.*);/i';
	const REGEX_DATA		= '/sprite-dataurl:\s*(.*);/i';
	
	const REGEX_SPRITE_TAG	= '(?:\s+.*;\s+){1,5}';
	const REGEX_SPRITE_REF	= 'sprite-ref:\s*';
	const REGEX_START_TAG	= '\/\*\*\s+';
	const REGEX_END_TAG		= '\*\/';
	const REGEX_BG_TAG		= '/background[-image]*:\s*url\((.*)\)(\s*.*);\s*';
	
	private $files 			= array();
	private $matches 		= array();
	private $sprites 		= array();
	
	private $css 			= '';
	private $css_suffix		= '_sprite';
	private $output_dir		= './';

	public function __construct($files, $output_dir=null, $css_output_dir=null) {
		$this->output_dir = $output_dir;
		$this->css_output_dir = $css_output_dir;
		
		if (isset($files)) {
			$this->parseFiles($files);
			$this->sortImages();
			$this->createSprite();
			$this->createCSS();
		}
	}
	
	public function parseFiles($files){
		if (!is_array($files))
			$files = array();

		foreach ($files as $file) {
			if (!file_exists($file))
				trigger_error("$file does not exist");
			
			if (!is_readable($file))
				trigger_error("$file is not readable");
				
			$this->files[] = $file;
			$this->css .= file_get_contents($file)."\n";
		}
		
		$regex =
			'/'.
			self::REGEX_START_TAG.
			'sprite\s*:.*;'.
			self::REGEX_SPRITE_TAG.
			self::REGEX_END_TAG.'/i';
		
		$matches = array();
		preg_match_all($regex, $this->css, $matches);
		
		$i=0;
		$matches = $matches[0];
		foreach($matches as $match){
			$this->matches[] = $match;
			// remove matche from css
			$this->css = preg_replace($regex, '', $this->css);
			
			$name = $this->parseTag(self::REGEX_NAME, $match);
			if (!empty($name)) {
			
				if (isset($this->sprites[$name]))
					continue;
			
				$url = trim($this->parseTag(self::REGEX_IMAGE, $match), '\'\""');

				$sprite = new StdClass();
				$sprite->url 		= $url;
				$sprite->filename 	= end(explode('/', $url));
				$sprite->layout 	= $this->parseTag(self::REGEX_LAYOUT, $match);
				$sprite->margin 	= (int)$this->parseTag(self::REGEX_MARGIN, $match);
				$sprite->bg 		= $this->parseTag(self::REGEX_BG, $match);
				$sprite->colors 	= $this->parseTag(self::REGEX_COLORS, $match);
				$sprite->images 	= $this->collectReferences($name);
				$sprite->type 		= $this->getImageTypeByExt($url);
				$sprite->width 		= 0;
				$sprite->height 	= 0;
				
				$this->sprites[$name] = $sprite;
			}
		}
	}
	
	private function parseTag($regex, $string) {
		preg_match($regex, $string, $matches);
		return (isset($matches[1])) ? $matches[1] : '';
	}
	
	private function collectReferences($name) {

		$regex = 
			self::REGEX_BG_TAG.
			self::REGEX_START_TAG.
			self::REGEX_SPRITE_REF.
			$name.'; '.
			self::REGEX_END_TAG.
			'/i';

		preg_match_all($regex, $this->css, $matches);
		
		$replaces = $matches[0];
		$locations = $matches[1];
		$suffixes = $matches[2];
		
		$i = 0;
		$images = array();
		foreach ($locations as $location => $val) {
			$name = trim($val,'\'\""');
			$image = new stdClass();
			
			if (!strpos('http', $name))
				$name = "http:" . $name;
			
			$image->location = $name;
			$image->replace = $replaces[$i];
			$image->suffix = $suffixes[$i];
			$image->repeat = $this->getBGRepeat($suffixes[$i]);
			$image->align = $this->getBGAlign($suffixes[$i]);
			
			list($width, $height, $type, $attr) = @getimagesize($name);
			
			if ($width == null || $height == null || $type == null)
				continue;
			
			$image->width = $width;
			$image->type = $type;
			$image->height = $height;
			
			$images[$name] = $image;
			$i++;
		}
		
		return $images;
	}
	
	private function getBGRepeat($str) {
		$str = str_replace(';', '', $str);
		$str = strtolower($str);
		$arr = explode(' ', $str);
		
		if (in_array('repeat-x', $arr))
			$result = 'repeat-x';
		elseif (in_array('repeat-y', $arr))
			$result = 'repeat-y';
		else
			$result = 'no-repeat';
			
		return $result;
	}
	
	private function getBGAlign($str) {
		$str = str_replace(';', '', $str);
		$str = strtolower($str);
		$arr = explode(' ', $str);
		
		if (in_array('left', $arr))
			$result = 'left';
		elseif (in_array('right', $arr))
			$result = 'right';
		else
			$result = '';
			
		return $result;
	}
	
	private function getImageTypeByExt($file) {
		$file = strtolower($file);
		$ext = end(explode('.', $file));
		switch ($ext) {
			case 'gif':
				return IMAGETYPE_GIF;
			break;
			
			case 'jpg':
			case 'jpeg':
				return IMAGETYPE_JPEG;
			break;
			
			case 'png':
				return IMAGETYPE_PNG;
			break;
			
			case 'bmp':
				return IMAGETYPE_BMP;
			break;
			
			case 'swf':
				return IMAGETYPE_SWF;
			break;
			
			case 'wbmp':
				return IMAGETYPE_WBMP;
			break;
			
			case 'xbm':
				return IMAGETYPE_XBM;
			break;

			default:
				return 0;
		}
	}
	
	private function sortImages() {

		foreach ($this->sprites as $name => &$sprite) {
		
			$sum_width  = 0;
			$sum_height = 0;
			
			$max_width = 0;
			$max_height = 0;
			
			$widthtarr = array();
			$heighttarr = array();
			
			$layout = $sprite->layout;
			$margin = $sprite->margin;
			
			if ($sprite->images) {
			
				foreach ($sprite->images as $iname => $image) {
					$widthtarr[$iname] = $image->width;
					$heighttarr[$iname] = $image->height;
					$sum_width  += $image->width+$margin;
					$sum_height += $image->height+$margin;
				}	
				
				// sprite images loop
				$sum_width  += $margin;
				$sum_height += $margin;
				
				$max_width = max($widthtarr);
				$max_height = max($heighttarr);
				
				$min_width = min($widthtarr);
				$min_height = min($heighttarr);
				
				switch ($layout) {
					case 'horizontal':
						array_multisort(
							$heighttarr, 
							SORT_NUMERIC,
							SORT_DESC, 
							$sprite->images
						);
						
						$sprite->height = $max_height;
						$sprite->width  = $sum_width;
					break;
					
					case 'vertical':
						array_multisort(
							$widthtarr,	
							SORT_NUMERIC,
							SORT_DESC, 
							$sprite->images
						);
						
						$sprite->height = $sum_height;
						$sprite->width  = $max_width;
					break;
					
					case 'mixed':
					break;
				}
				
				$sum_width  = $margin;
				$sum_height = $margin;
				
				switch ($layout) {
					case 'horizontal':
						$sum_height = 0;
					break;
					case 'vertical':
						$sum_width = 0;
					break;
					case 'mixed':
					break;
				}

				foreach ($sprite->images as $iname => $image) {

					switch ($image->align) {
						case 'left':
							$image->spritepos_left = 0;
						break;
						case 'right':
							$image->spritepos_left = $max_width-$image->width;
						break;
					}

					switch ($layout) {
						case 'horizontal':
							$image->spritepos_top = $sum_height;
							$image->spritepos_left = $sum_width;
							$sum_width += $image->width+$margin;
						break;
						case 'vertical':
							$image->spritepos_top = $sum_height;
							$image->spritepos_left = $sum_width;
							$sum_height += $image->height+$margin;
						break;
					}
					
					$sprite->images[$iname] = $image;
				}
			}
		}
	}
	
	private function createCSS() {
		$css = $this->css;
		foreach($this->sprites as $sprite) {
			$bgurl = 'background: url(\''.$sprite->url.'\')';
			if ($sprite->images) {
				foreach($sprite->images as $iname => $image) {
					$top  = $image->spritepos_top;
					$left = $image->spritepos_left;

					$suffix = $image->suffix.';';
					$bgpos = 'background-position: -'.$left.'px -'.$top.'px;';

					switch ($image->align) {
						case 'left':
							$image->spritepos_left = 0;
						break;
						case 'right':
							$bgpos = 'background-position: right -'.$top.'px;';
						break;
					}
					
					$replace = $bgurl.$suffix.$bgpos;
					$css = str_replace($image->replace, $replace, $css);
				}
			}
		}

		$handle = @fopen(realpath($this->css_output_dir).'/sprites.css', "w");
		if (!$handle)
			trigger_error("Can't write to {$this->css_output_dir}sprites.css");
			
		fwrite ($handle, $css);
		fclose($handle);
	}
	
	private function createSprite() {
		foreach ($this->sprites as $name => $sprite) {
			$filename = $sprite->filename;
			if ($sprite->images) {
				switch ($sprite->type) {
				
					case IMAGETYPE_PNG:
						$sprite_data = imagecreatetruecolor($sprite->width, $sprite->height);
						imagealphablending($sprite_data, false);
						imagesavealpha($sprite_data, true);
						$transparent = imagecolorallocatealpha($sprite_data, 255, 255, 255, 127);
						$transparent = imagecolorallocatealpha($sprite_data, 255, 255, 255, 127);
						imagecolortransparent($sprite_data, $transparent);
						imagefilledrectangle($sprite_data, 0, 0, $sprite->width, $sprite->height, $transparent);
					break;
				
					case IMAGETYPE_JPEG:
						$sprite_data = imagecreatetruecolor($sprite->width, $sprite->height);
						$transparent = imagecolorallocate($sprite_data, 255, 255, 255);	// white
						imagefilledrectangle($sprite_data, 0, 0, $sprite->width, $sprite->height, $transparent);
					break;

					case IMAGETYPE_GIF:
						$sprite_data = imagecreate($sprite->width, $sprite->height);
					break;
				}
				
				foreach ($sprite->images as $iname => $image) {
				
					switch ($image->type) {
					
						case IMAGETYPE_PNG:
							$image_data = @imagecreatefrompng($image->location);
						break;
					
						case IMAGETYPE_JPEG:
							$image_data = @imagecreatefromjpeg($image->location);
						break;
					
						case IMAGETYPE_GIF:
							$image_data = @imagecreatefromgif($image->location);
						break;
	
						case IMAGETYPE_SWF:
							$image_data = @imagecreatefromswf($image->location);
						break;
							
						case IMAGETYPE_WBMP:
							$image_data = @imagecreatefromwbmp($image->location);
						break;
						
						case IMAGETYPE_XBM:
							$image_data = @imagecreatefromxbm($image->location);
						break;
					}

					switch ($image->align) {
						case 'left':
							$image->spritepos_left = 0;
						break;
						case 'right':
							$image->spritepos_left = $sprite->width - $image->width;
						break;
					}

					// stretching to full width:
					switch ($image->repeat) {
						case 'repeat-x':
							imagecopyresampled(
								$sprite_data,
								$image_data,
								$image->spritepos_left,
								$image->spritepos_top,
								0,
								0,
								$sprite->width,
								$image->height,
								$image->width,
								$image->height
							);
						break;
						
						case 'repeat-y':
							imagecopyresampled(
								$sprite_data,
								$image_data,
								$image->spritepos_left,
								$image->spritepos_top,
								0,
								0,
								$image->width,
								$image->height,
								$image->width,
								$image->height
							);
						break;
						
						default:
							imagecopy(
								$sprite_data,
								$image_data,
								$image->spritepos_left,
								$image->spritepos_top,
								0,
								0,
								$image->width,
								$image->height
							);
					}
					imagedestroy($image_data);
				}
				
				
				$result = 0;
				$file = realpath($this->output_dir)."/".$sprite->filename;
				switch ($sprite->type) {
				
					case IMAGETYPE_GIF:
						$result = @imagegif($sprite_data, $file);
					break;
					
					case IMAGETYPE_JPEG:
						$result = @imagejpeg($sprite_data, $file);
					break;
					
					case IMAGETYPE_PNG:
						$result = @imagepng($sprite_data, $file);
					break;
					
					default:
						$result = @imagepng($sprite_data, $file);
				}
				imagedestroy($sprite_data);
			}
		}
	}
}