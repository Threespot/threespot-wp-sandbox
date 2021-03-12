<?php

namespace SearchWP\Dependencies\Psr\Http\Client;

use SearchWP\Dependencies\Psr\Http\Message\RequestInterface;
use SearchWP\Dependencies\Psr\Http\Message\ResponseInterface;
interface ClientInterface
{
    /**
     * Sends a PSR-7 request and returns a PSR-7 response.
     *
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface If an error happens while processing the request.
     */
    public function sendRequest(\SearchWP\Dependencies\Psr\Http\Message\RequestInterface $request) : \SearchWP\Dependencies\Psr\Http\Message\ResponseInterface;
}
