<?php


namespace MapSVG;


class MarkersRepository extends FilesRepository
{

	public $modelClass = 'Marker';

	public function __construct()
	{
		parent::__construct(array(MAPSVG_PINS_DIR, MAPSVG_UPLOADS_DIR . DIRECTORY_SEPARATOR . 'markers'), MAPSVG_UPLOADS_DIR . DIRECTORY_SEPARATOR . 'markers');
		$this->fileTypes = array('gif', 'png', 'jpg', 'jpeg', 'svg', 'webp');
	}
}
