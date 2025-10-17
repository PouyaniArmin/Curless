<?php

namespace Armin\Curless;

use CURLFile;
use CurlHandle;
use Exception;

class Request
{
    private ?CurlHandle $curlHandler = null;
    private string $url;
    private string $method;
    private array $headers = [];
    private array $query = [];
    private mixed $body = null;
    private array $files = [];
    private int $timeout = 10;
    private bool $verifySSL = true;
    // 
    public function url(string $url)
    {
        $this->url = $url;
        return $this;
    }
    // 
    public function method(string $method)
    {
        $this->method = strtoupper($method);
        return $this;
    }
    // 
    public function headers(array $headers)
    {
        $this->headers = $headers;
        return $this;
    }
    public function query(array $query)
    {
        $this->query = $query;
        return $this;
    }
    // 
    public function body(mixed $body)
    {
        $this->body = $body;
        return $this;
    }
    // 
    public function files(array $files)
    {
        $this->files = $files;
        return $this;
    }
    public function timeout(int $timeout)
    {
        $this->timeout = $timeout;
        return $this;
    }
    public function verifySSL(bool $verifySSL)
    {
        $this->verifySSL = $verifySSL;
        return $this;
    }
    public function send()
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
    private function validate(): void
    {
        if (!isset($this->url) || !isset($this->method)) {
            throw new Exception("Error: URL and HTTP method must be before calling send()");
        }
    }
    private function setCurlOption()
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
    private function methodHandler($method)
    {
        if (isset($method)) {
            $method = strtoupper($method);
            switch ($method) {
                case 'GET':
                    curl_setopt($this->curlHandler, CURLOPT_HTTPGET, true);
                    break;
                case 'POST':
                    curl_setopt($this->curlHandler, CURLOPT_POST, true);

                    break;
                case 'PUT':
                    curl_setopt($this->curlHandler, CURLOPT_CUSTOMREQUEST, 'PUT');
                    break;
                case 'PATCH':
                    curl_setopt($this->curlHandler, CURLOPT_CUSTOMREQUEST, 'PATCH');
                    break;
                case 'HEAD':
                    curl_setopt($this->curlHandler, CURLOPT_NOBODY, true);
                    break;
                case 'DELETE':
                    curl_setopt($this->curlHandler, CURLOPT_CUSTOMREQUEST, 'DELETE');

                    break;
                default:
                    $method = 'GET';
                    curl_setopt($this->curlHandler, CURLOPT_HTTPGET, true);
                    break;
            }
        }
    }
    private function headerHandler($header): array
    {
        $data = [];
        if (isset($header)) {
            foreach ($header as $key => $value) {
                $data[] = $key . ": " . $value;
            }
        }
        return $data;
    }

    private function bodyHandler(mixed $data)
    {
        $contentType = $this->headers['Content-Type'] ?? '';
        if (empty($data)) {
            return null;
        }
        if (!$contentType) {
            throw new Exception("Error Content-Type header must be set when sending a request body.");
        }

        switch ($contentType) {
            case 'application/json':
                $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception("JSON encode error: " . json_last_error_msg());
                }
                return $json;
                break;
            case 'application/x-www-form-urlencoded':
                return http_build_query($data);
                break;
            case 'multipart/form-data':
                return $this->fileHandler($this->files);
                break;
            default:
                throw new Exception("Error: Unsupported Content-Type : $contentType");
                break;
        }
    }

    private function fileHandler(array $files)
    {
        $result = is_array($this->body) ? $this->body : [];
        foreach ($files as $field => $path) {
            if (!file_exists($path)) {
                throw new Exception("multipart/form-data error: file not found for field '$field': $path");
            }
            $result[$field] = new \CURLFile($path);
        }

        return $result;
    }
    private function queryHandler(): string
    {
        return !empty($this->query) ? '?' . http_build_query($this->query) : '';
    }
}
