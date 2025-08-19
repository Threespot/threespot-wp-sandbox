<?php

namespace MapSVG;

class ApiClient
{
  private $baseUrl;
  private $httpClient;
  private $headers;

  public function __construct($baseUrl, $auth = null)
  {
    $this->baseUrl = rtrim($baseUrl, '/');
    $this->httpClient = new \GuzzleHttp\Client();
    $this->headers = [
      'Content-Type' => 'application/json',
      'Accept' => 'application/json',
    ];

    if ($auth && $auth["type"] && $auth["token"]) {
      $this->headers['Authorization'] = $auth["type"] . ' ' . $auth["token"];
    }
  }

  public function get($endpoint, $params = [])
  {
    return $this->request('GET', $endpoint, ['query' => $params]);
  }

  public function post($endpoint, $data = [])
  {
    return $this->request('POST', $endpoint, ['json' => $data]);
  }

  public function put($endpoint, $data = [])
  {
    return $this->request('PUT', $endpoint, ['json' => $data]);
  }

  public function delete($endpoint, $params = [])
  {
    return $this->request('DELETE', $endpoint, ['query' => $params]);
  }

  private function request($method, $endpoint, $options = [])
  {
    $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');

    $options['headers'] = $this->headers;

    try {
      $response = $this->httpClient->request($method, $url, $options);
      return json_decode($response->getBody(), true);
    } catch (\GuzzleHttp\Exception\RequestException $e) {
      throw new ApiException(wp_kses($e->getMessage(), array()), wp_kses($e->getCode(), array()), wp_kses($e, array()));
    }
  }

  public function setHeader($key, $value)
  {
    $this->headers[$key] = $value;
  }
}

class ApiException extends \Exception {}
