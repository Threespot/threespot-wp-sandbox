<?php


namespace MapSVG;


interface FileInterface {
	public function setName($name);
	public function getName();
	public function setServerPath($path);
	public function getRelativeUrl();
	public function setBody($data);
	public function getBody();
}
