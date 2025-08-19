<?php


namespace MapSVG;

/**
 * Class that contains parameters for the database query: sort, filters, page, etc.
 * @package MapSVG
 */
class Query {

	// List of the fields that should be retrieved from the database
	public $fields = array();
	public $filters = array();
	public $filterout = array();
	public $search = '';
	public $searchFallback = false;
	public $withSchema = false;
	public $page    = 1;
	public $perpage = 30;
	public $sort    = array();

	public function __construct($params)
	{
		if(isset($params['withSchema'])){
			$this->withSchema = filter_var($params['withSchema'], FILTER_VALIDATE_BOOLEAN);
		}
		if(isset($params['filters'])){
			$this->filters = (array)$params['filters'];
		}
		if(isset($params['filterout'])){
			$this->filterout = (array)$params['filterout'];
		}
		if(isset($params['page'])){
			$this->page = (int)$params['page'];
		}
		if(isset($params['perpage'])){
			$this->perpage = (int)$params['perpage'];
		}
		if(isset($params['sort'])){
			$this->sort = $params['sort'];
		}
		if(isset($params['search'])){
			$this->search = $params['search'];
		}
		if(isset($params['searchFallback'])){
			$this->searchFallback = filter_var($params['searchFallback'], FILTER_VALIDATE_BOOLEAN);
		}
	}

	public function __get($name)
	{
		if (isset($this->{$name})){
			return $this->{$name};
		} else {
			return null;
		}
	}
}
