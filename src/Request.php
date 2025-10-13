<?php

namespace Armin\Curless;

use CURLFile;
use CurlHandle;
use Exception;

class Request
{
    private ?CurlHandle $curlHandler=null;
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
        $urlWithQuery = $this->url;
        if (!empty($this->query)) {
            $urlWithQuery .= $this->queryHandler();
        }
        $this->curlHandler = curl_init($urlWithQuery);
        $this->methodHandler($this->method);
        curl_setopt($this->curlHandler, CURLOPT_HTTPHEADER, $this->headerHandler($this->headers));
        curl_setopt($this->curlHandler, CURLOPT_RETURNTRANSFER, true);
        if ($this->body!==null) {
            curl_setopt($this->curlHandler, CURLOPT_POSTFIELDS, $this->bodyHandler($this->body));
            
        }
        curl_setopt($this->curlHandler, CURLOPT_SSL_VERIFYPEER, $this->verifySSL);
        curl_setopt($this->curlHandler, CURLOPT_SSL_VERIFYHOST, $this->verifySSL ? 2 : 0);
        curl_setopt($this->curlHandler, CURLOPT_TIMEOUT, $this->timeout);
        $response = curl_exec($this->curlHandler);
        if (curl_errno($this->curlHandler)) {
            throw new Exception("cURL Error: " . curl_error($this->curlHandler));
        }
        curl_close($this->curlHandler);
        return $response;
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
        if ($contentType === 'application/json') {
            $response = json_encode($data,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return $response;
        }
        if ($contentType === 'application/x-www-form-urlencoded') {
            return http_build_query($data);
            
        }
        if ($contentType === 'multipart/form-data') {
            return $this->fileHandler($this->files);
        }
        return null;
    }

    private function fileHandler(array $files)
    {
        $result = is_array($this->body) ? $this->body : [];
        foreach ($files as $field => $path) {
            if (!file_exists($path)) {
                throw new Exception("File not found: $path");
            }
            $result[$field] = new \CURLFile($path);
        }

        return $result;
    }
    private function queryHandler(): string
    {
        return !empty($this->query)? '?'. http_build_query($this->query):'';
    }
}
