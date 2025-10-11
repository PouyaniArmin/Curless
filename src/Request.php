<?php

namespace Armin\Curless;

use CURLFile;
use CurlHandle;
use Exception;

class Request
{
    private CurlHandle $curlHandler;
    private string $url;
    private string $method;
    private array $headers = [];
    private array $query = [];
    private mixed $body = null;
    private array $files = [];
    private int $timeout = 10;
    private bool $verifySSL = true;
    public function url(string $url)
    {
        $this->url = $url;
        return $this;
    }
    public function method(string $method)
    {
        $this->method = strtoupper($method);
        return $this;
    }
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
    public function body(mixed $body)
    {
        $this->body = $body;
        $this->bodyHandler($this->body);
        return $this;
    }
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
        $this->curlHandler = curl_init($this->url);
        $this->methodHandler($this->method);
        curl_setopt($this->curlHandler, CURLOPT_HTTPHEADER, $this->headerHandler($this->headers));
        curl_setopt($this->curlHandler, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curlHandler,CURLOPT_POSTFIELDS,$this->bodyHandler($this->body));
        $response=curl_exec($this->curlHandler);
        if (curl_errno($this->curlHandler)) {
            throw new Exception("Error:" . $this->curlHandler);
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
                    if (isset($this->body)) {
                        curl_setopt($this->curlHandler, CURLOPT_POSTFIELDS, $this->body);
                    }
                    break;
                case 'PUT':
                    curl_setopt($this->curlHandler, CURLOPT_CUSTOMREQUEST, 'PUT');
                    if (isset($this->body)) {
                        curl_setopt($this->curlHandler, CURLOPT_POSTFIELDS, $this->body);
                    }
                    break;
                case 'DELETE':
                    curl_setopt($this->curlHandler, CURLOPT_CUSTOMREQUEST, 'DELETE');
                    if (isset($this->body)) {
                        curl_setopt($this->curlHandler, CURLOPT_POSTFIELDS, $this->body);
                    }
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
        $contetType = $this->headers['Content-Type'] ?? '';
        if ($contetType === 'application/json') {
            $response = json_encode($data, JSON_PRETTY_PRINT);
            return $response;
        }
        if ($contetType === 'application/x-www-form-urlencoded') {
            $test = '';
            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    $test .= $key . "=" . $value . '&';
                }
            } else {
                throw new Exception("Error: Data body invalied");
            }
            return substr($test, 0, -1);
        }
        if ($contetType === 'multipart/form-data') {
            return $this->fileHandler($this->files);
        }
        
    }

    private function fileHandler(array $files)
    {
        $result = $this->body;
        foreach ($files as $field => $path) {
            if (!file_exists($path)) {
                throw new Exception("File not found: $path");
            }
            $result[$field] = new \CURLFile($path);
        }

        return $result;
    }
}
