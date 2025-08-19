<?php

namespace MapSVG;

class ApiDataSource implements DataSourceInterface
{
  private $apiClient;
  private $apiEndpoints;
  private $schema;
  /**
   * @var Schema $schema
   */
  public function __construct($schema)
  {
    $this->apiClient = new ApiClient($schema->apiBaseUrl, $schema->apiAuthorization);
    $this->apiEndpoints = $schema->apiEndpoints;
    $this->schema = $schema;
  }

  function findEndpointByName(string $name): ?array
  {
    foreach ($this->apiEndpoints as $endpoint) {
      if (isset($endpoint['name']) && $endpoint['name'] === $name) {
        return $endpoint;
      }
    }
    return null;
  }

  public function getApiEndpoint(string $name, $model = null)
  {
    $endpoint = $this->findEndpointByName($name);

    if (!$endpoint) {
      return null;
    }

    $model = (array)$model;

    $url = preg_replace_callback('/\[:(\w+)\]/', function ($matches) use ($model) {
      $key = $matches[1];
      return isset($model[$key]) ? $model[$key] : "[:$key]";
    }, $endpoint['url']);

    return [
      'url' => $url,
      'method' => $endpoint['method'],
      'name' => $endpoint['name'],
    ];
  }

  public function find($criteria)
  {
    $criteria = (array)$criteria;
    $endpoint = $this->getApiEndpoint("index", $criteria);
    if (!$endpoint) {
      return ["error" => "No endpoint"];
    }
    try {
      $res = $this->apiClient->get($endpoint["url"], $criteria);
      return $res;
    } catch (\Exception $e) {

      return ["error" => "Can't get data from API endpoint."];
    }
  }

  public function findOne($criteria)
  {
    $endpoint = $this->getApiEndpoint("show", $criteria);
    return $this->apiClient->get($endpoint["url"], $criteria);
  }


  public function create($data)
  {
    $endpoint = $this->getApiEndpoint("create", $data);
    return $this->apiClient->post($endpoint["url"], $data);
  }

  public function update($data, $criteria)
  {
    $endpoint = $this->getApiEndpoint("create", $criteria);
    return $this->apiClient->put($endpoint["url"], $data);
  }

  public function delete($id)
  {
    $endpoint = $this->getApiEndpoint("delete", ["id" => $id]);
    return $this->apiClient->delete($endpoint["url"]);
  }

  public function clear()
  {
    $endpoint = $this->getApiEndpoint("clear");
    return $this->apiClient->delete($endpoint["url"]);
  }
}
