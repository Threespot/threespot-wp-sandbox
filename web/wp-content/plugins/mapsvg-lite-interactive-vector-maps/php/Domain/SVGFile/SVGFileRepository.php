<?php


namespace MapSVG;


class SVGFileRepository extends FilesRepository {

	public $modelClass = 'SVGFile';

	public function __construct() {
		parent::__construct(array(MAPSVG_MAPS_DIR, MAPSVG_UPLOADS_DIR), MAPSVG_UPLOADS_DIR);
	}

}
