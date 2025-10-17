<?php

namespace Armin\Curless;

use CurlHandle;

class Client
{
    /**
     * Instance of Request class
     *
     * @var Request
     */
    protected Request $request;

    /**
     * Instance of Response class
     *
     * @var Response
     */
    protected Response $response;

    /**
     * Initialize a new request with HTTP method and URL.
     *
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $url Target URL for the request
     * @return self
     */
    public function request(string $method, string $url): self
    {
        $this->request = new Request();
        $this->request->url($url)->method($method);
        return $this;
    }

    /**
     * Set headers for the request.
     *
     * @param array $headers Associative array of headers
     * @return self
     */
    public function headers(array $headers): self
    {
        $this->request->headers($headers);
        return $this;
    }

    /**
     * Set request body.
     *
     * @param mixed $data Body data (array, string, etc.)
     * @return self
     */
    public function body(mixed $data): self
    {
        $this->request->body($data);
        return $this;
    }

    /**
     * Attach files for multipart/form-data request.
     *
     * @param array $files Associative array [field => path]
     * @return self
     */
    public function files(array $files): self
    {
        $this->request->files($files);
        return $this;
    }

    /**
     * Set query parameters for the request.
     *
     * @param array $data Associative array of query parameters
     * @return self
     */
    public function query(array $data): self
    {
        $this->request->query($data);
        return $this;
    }

    /**
     * Set request timeout in seconds.
     *
     * @param int $timeout Timeout value
     * @return self
     */
    public function timeout(int $timeout): self
    {
        $this->request->timeout($timeout);
        return $this;
    }

    /**
     * Enable or disable SSL verification.
     *
     * @param bool $verifySSL True to verify SSL, false to skip
     * @return self
     */
    public function verifySSL(bool $verifySSL): self
    {
        $this->request->verifySSL($verifySSL);
        return $this;
    }

    /**
     * Send the prepared request and return the raw response array.
     *
     * @return array Response data from Request::send()
     */
    public function send(): array
    {
        return $this->request->send();
    }

    /**
     * Initialize Response object from raw response data.
     *
     * @param array $data Raw response array
     * @return self
     */
    public function response(array $data): self
    {
        $this->response = new Response($data);
        return $this;
    }

    /**
     * Get the response body.
     *
     * @return mixed Response body content
     */
    public function getBody()
    {
        return $this->response->bodyInfo();
    }

    /**
     * Get the response headers.
     *
     * @return array Response headers
     */
    public function getHeaders()
    {
        return $this->response->headerInfo();
    }

    /**
     * Get the HTTP status code.
     *
     * @return int HTTP status code
     */
    public function getStatus()
    {
        return $this->response->status();
    }

    /**
     * Get all response data.
     *
     * @return array Full response array
     */
    public function getInfo()
    {
        return $this->response->info();
    }

    /**
     * Decode response body as JSON.
     *
     * @return mixed Decoded JSON data
     * @throws Exception If JSON decoding fails
     */
    public function json()
    {
        return $this->response->json();
    }
}