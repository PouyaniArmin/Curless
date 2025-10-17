<?php

namespace Armin\Curless;

use CURLFile;
use CurlHandle;
use Exception;

class Request
{
    /**
     * cURL handle instance (nullable)
     * 
     * @var CurlHandle|null
     */
    private ?CurlHandle $curlHandler = null;

    /**
     * Target URL for the HTTP request
     * 
     * @var string
     */
    private string $url;

    /**
     * HTTP method (GET, POST, PUT, DELETE, etc.)
     * 
     * @var string
     */
    private string $method;

    /**
     * HTTP headers for the request
     * 
     * @var array
     */
    private array $headers = [];

    /**
     * Query parameters to append to the URL
     * 
     * @var array
     */
    private array $query = [];

    /**
     * Request body (array, string, etc.)
     * 
     * @var mixed
     */
    private mixed $body = null;

    /**
     * Files for multipart/form-data requests
     * 
     * @var array
     */
    private array $files = [];

    /**
     * Timeout for the request in seconds
     * 
     * @var int
     */
    private int $timeout = 10;

    /**
     * Flag to enable or disable SSL verification
     * 
     * @var bool
     */
    private bool $verifySSL = true;

    /**
     * Set the request URL.
     *
     * @param string $url The target URL.
     * @return self
     */
    public function url(string $url): self
    {
        $this->url = $url;
        return $this;
    }
    
    /**
     * Set the HTTP method for the request.
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE, etc.)
     * @return self
     */
    public function method(string $method): self
    {
        $this->method = strtoupper($method);
        return $this;
    }
     
    /**
     * Set request headers.
     *
     * @param array $headers Associative array of headers.
     * @return self
     */
    public function headers(array $headers): self
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * Set query parameters.
     *
     * @param array $query Associative array of query parameters.
     * @return self
     */
    public function query(array $query): self
    {
        $this->query = $query;
        return $this;
    }
     
    /**
     * Set the request body.
     *
     * @param mixed $body Body content (array, string, etc.)
     * @return self
     */
    public function body(mixed $body): self
    {
        $this->body = $body;
        return $this;
    }
     
    /**
     * Set files for multipart/form-data requests.
     *
     * @param array $files Associative array [field => path].
     * @return self
     */
    public function files(array $files): self
    {
        $this->files = $files;
        return $this;
    }

    /**
     * Set request timeout in seconds.
     *
     * @param int $timeout Timeout in seconds.
     * @return self
     */
    public function timeout(int $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * Enable or disable SSL certificate verification.
     *
     * @param bool $verifySSL True to verify SSL, false to skip.
     * @return self
     */
    public function verifySSL(bool $verifySSL): self
    {
        $this->verifySSL = $verifySSL;
        return $this;
    }

    /**
     * Send the HTTP request and return the response.
     *
     * @return array Response data including 'body', 'headers', 'status', and 'info'.
     * @throws Exception If URL/method not set or cURL error occurs.
     */
    public function send(): array
    {
        $this->validate();
        $urlWithQuery = $this->url;

        if (!empty($this->query)) {
            $urlWithQuery .= $this->queryHandler();
        }

        $this->curlHandler = curl_init($urlWithQuery);
        $this->methodHandler($this->method);
        $this->setCurlOption();

        $rawResponse = curl_exec($this->curlHandler);

        if (curl_errno($this->curlHandler)) {
            throw new Exception("cURL Error: " . curl_error($this->curlHandler));
        }

        return $this->parseResponse($rawResponse);
    }

    /**
     * Validate that URL and HTTP method are set before sending request.
     *
     * @return void
     * @throws Exception
     */
    private function validate(): void
    {
        if (!isset($this->url) || !isset($this->method)) {
            throw new Exception("Error: URL and HTTP method must be set before calling send()");
        }
    }

    /**
     * Set cURL options for the request.
     *
     * @return void
     */
    private function setCurlOption(): void
    {
        curl_setopt($this->curlHandler, CURLOPT_HTTPHEADER, $this->headerHandler($this->headers));
        curl_setopt($this->curlHandler, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curlHandler, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->curlHandler, CURLOPT_HEADER, true);

        if ($this->body !== null) {
            curl_setopt($this->curlHandler, CURLOPT_POSTFIELDS, $this->bodyHandler($this->body));
        }

        curl_setopt($this->curlHandler, CURLOPT_SSL_VERIFYPEER, $this->verifySSL);
        curl_setopt($this->curlHandler, CURLOPT_SSL_VERIFYHOST, $this->verifySSL ? 2 : 0);
        curl_setopt($this->curlHandler, CURLOPT_TIMEOUT, $this->timeout);
    }

    /**
     * Parse raw response headers into structured array.
     *
     * @param string $headerString Raw headers from cURL.
     * @return array Parsed headers.
     */
    private function parseHeaders(string $headerString): array
    {
        $responseHeader = [];
        $currentBlock = null;
        $headerLines = explode("\r\n", $headerString);

        foreach ($headerLines as $line) {
            $line = trim($line);
            if ($line === '') {
                if ($currentBlock !== null) {
                    $responseHeader[] = $currentBlock;
                    $currentBlock = null;
                }
                continue;
            }

            if (strpos($line, 'HTTP/') === 0) {
                if ($currentBlock !== null) {
                    $responseHeader[] = $currentBlock;
                }
                [$version, $status] = explode(' ', $line, 2);
                $currentBlock = ['Version' => trim($version), 'Status Code' => trim($status)];
            } else {
                if (strpos($line, ':') !== false) {
                    [$key, $value] = explode(":", $line, 2);
                    $currentBlock[trim($key)] = trim($value);
                }
            }
        }

        if ($currentBlock !== null) {
            $responseHeader[] = $currentBlock;
        }

        return $responseHeader;
    }

    /**
     * Parse raw cURL response into body, headers, status, and info.
     *
     * @param string $rawResponse Raw response from cURL.
     * @return array Parsed response.
     */
    private function parseResponse(string $rawResponse): array
    {
        $headerSize = curl_getinfo($this->curlHandler, CURLINFO_HEADER_SIZE);
        $headerString = substr($rawResponse, 0, $headerSize);
        $body = substr($rawResponse, $headerSize);

        $responseHeader = $this->parseHeaders($headerString);
        $info = curl_getinfo($this->curlHandler);
        $status = $info['http_code'] ?? 0;
        curl_close($this->curlHandler);

        return [
            'body' => $body,
            'headers' => end($responseHeader),
            'status' => $status,
            'info' => $info
        ];
    }

    /**
     * Set cURL method option based on HTTP method.
     *
     * @param string $method HTTP method.
     * @return void
     */
    private function methodHandler(string $method): void
    {
        $method = strtoupper($method);

        switch ($method) {
            case 'GET':
                curl_setopt($this->curlHandler, CURLOPT_HTTPGET, true);
                break;
            case 'POST':
                curl_setopt($this->curlHandler, CURLOPT_POST, true);
                break;
            case 'PUT':
            case 'PATCH':
            case 'DELETE':
                curl_setopt($this->curlHandler, CURLOPT_CUSTOMREQUEST, $method);
                break;
            case 'HEAD':
                curl_setopt($this->curlHandler, CURLOPT_NOBODY, true);
                break;
            default:
                curl_setopt($this->curlHandler, CURLOPT_HTTPGET, true);
                break;
        }
    }

    /**
     * Convert header array into cURL-compatible format.
     *
     * @param array $header Associative array of headers.
     * @return array
     */
    private function headerHandler(array $header): array
    {
        $data = [];
        foreach ($header as $key => $value) {
            $data[] = $key . ": " . $value;
        }
        return $data;
    }

    /**
     * Prepare request body based on Content-Type header.
     *
     * @param mixed $data Body content.
     * @return mixed
     * @throws Exception
     */
    private function bodyHandler(mixed $data): mixed
    {
        $contentType = $this->headers['Content-Type'] ?? '';

        if (empty($data)) {
            return null;
        }

        if (!$contentType) {
            throw new Exception("Error: Content-Type header must be set when sending a request body.");
        }

        switch ($contentType) {
            case 'application/json':
                $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception("JSON encode error: " . json_last_error_msg());
                }
                return $json;

            case 'application/x-www-form-urlencoded':
                return http_build_query($data);

            case 'multipart/form-data':
                return $this->fileHandler($this->files);

            default:
                throw new Exception("Error: Unsupported Content-Type: $contentType");
        }
    }

    /**
     * Attach files for multipart/form-data requests.
     *
     * @param array $files Associative array [field => path].
     * @return array
     * @throws Exception If a file does not exist.
     */
    private function fileHandler(array $files): array
    {
        $result = is_array($this->body) ? $this->body : [];

        foreach ($files as $field => $path) {
            if (!file_exists($path)) {
                throw new Exception("multipart/form-data error: file not found for field '$field': $path");
            }
            $result[$field] = new CURLFile($path);
        }

        return $result;
    }

    /**
     * Convert query array to URL-encoded string.
     *
     * @return string
     */
    private function queryHandler(): string
    {
        return !empty($this->query) ? '?' . http_build_query($this->query) : '';
    }
}