<?php

namespace MapSVG;

class SVGFile extends File {

	/**
	 * @param int $width
	 * @param int $height
	 * @param string $png
	 * @param array $bounds
	 * @param int|null $mapId
	 *
	 * @return SVGFile
	 * @throws \Exception
	 */
	public static function download($width, $height, $png, $bounds, $mapId = null)
	{

		if (empty($png) || empty($bounds)){
			throw new \Exception('No PNG or bounds.', 400);
		}

		$bounds = implode(' ',$bounds);

		$width = (int)$width;
		$height = (int)$height;

		$filename = 'mapsvg' . ($mapId?'-'.$mapId:'') . '.svg';

		// $mapsvg_error = \MapSVG\FilesRepository::checkUploadDir();

		// if (!$mapsvg_error) {
			$svg = '';
			$svg .= '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
    <svg
        xmlns:mapsvg="http://mapsvg.com"
        xmlns:xlink="http://www.w3.org/1999/xlink"    
        xmlns:dc="http://purl.org/dc/elements/1.1/"
        xmlns:cc="http://creativecommons.org/ns#"
        xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
        xmlns:svg="http://www.w3.org/2000/svg"  
        xmlns="http://www.w3.org/2000/svg"
        width="' . $width*20 . '"
        height="' . $height*20 . '"
        mapsvg:geoViewBox="'.$bounds.'"
    >
    ';
			$svg .= '<image id="mapsvg-google-map-background" xlink:href="' . $png . '"  x="0" y="0" height="' . $height*20 . '" width="' . $width*20 . '"></image>';
			$svg .= '</svg>';

			$file = new static(array('name' => $filename, 'body' => $svg));
			$file->save();

			return $file;
		// }
	}

	public function lastChanged(){
		if(file_exists($this->serverPath)){
			return filemtime($this->serverPath);
		} else {
			return 0;
		}
	}

}
