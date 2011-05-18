<?php
namespace Milk\Utils;

class PDF {

	public static function reduceSize($file) {
		if (!file_exists($file))
			return false;
		
		//exec("gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/ebook -dNOPAUSE -dQUIET -dBATCH -sOutputFile=$file $file");
		//exec("convert -compress JPEG -quality 100 $file $file");
		
		//exec("export OMP_NUM_THREADS=2");
		//exec("export OMP_DYNAMIC=TRUE");
		//exec("nice -n 15 pdf2djvu -d 600 --loss-level=50 --bg-subsample=3 --bg-slices=74+13+10 -q $file -o $file.djvu > /dev/null");
		//exec("nice -n 15 all2djvu -dpi 600 -autocolor -deskew -q $file -o $file.djvu > /dev/null");
		//exec("nice -n 15 djvups $file.djvu $file.ps > /dev/null");
		
		//exec("nice -n 15 pdftops -paper 'A4' -level3 -duplex -q $file $file.ps > /dev/null");
		//exec("nice -n 15 pstopnm $file.ps > /dev/null");
		//exec("nice -n 15 unpaper $file.pnm $file.pnm > /dev/null");
		//exec("nice -n 15 ps2pdf -r300 -dProcessColorModel=/DeviceRGB -dCompatibility=1.4 -dPDFX=true -dPDFSETTINGS=/screen -dNOPAUSE -dBATCH -dQUIET -dAutoRotatePages=/None -dUseFlateCompression=false -dLZWEncodePages=true -dCompressFonts=true -dOptimize=true -dDownsampleColorImages=true -dDownsampleGrayImages=true -dDownsampleMonoImages=true -dUseCIEColor -dColorConversionStrategy=/sRGB $file.ps $file.tmp > /dev/null");
		
		exec("nice -n 15 a2ping --extra='-preload' --extra='-level3' $file $file.ps");
		exec("nice -n 15 ps2pdf -dProcessColorModel=/DeviceRGB -dCompatibility=1.4 -dPDFX=true -dPDFSETTINGS=/screen -r144 -dNOPAUSE -dBATCH -dQUIET -dAutoRotatePages=/None -dUseFlateCompression=false -dLZWEncodePages=true -dCompressFonts=true -dOptimize=true -dDownsampleColorImages=true -dDownsampleGrayImages=true -dDownsampleMonoImages=true -dUseCIEColor -dColorConversionStrategy=/sRGB $file.ps $file.tmp.pdf");
		
		@unlink("$file.ps");
		if (filesize("$file.tmp.pdf") < filesize($file)) {
			@unlink($file);
			@rename("$file.tmp.pdf", $file);
		} else {
			@unlink("$file.tmp.pdf");
		}
		//exec("nice -n 15 a2ping $file");
	}

}