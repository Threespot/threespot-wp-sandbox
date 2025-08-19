<?php

namespace MapSVG;

interface DataSourceInterface
{
  public function find($criteria);
  public function findOne($criteria);
  public function create($data);
  public function update($data, $criteria);
  public function delete($criteria);
}
