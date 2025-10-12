<?php

namespace Armin\Curless;

use CurlHandle;

class Client
{
    protected Request $request;

    public function request(string $method, string $url)
    {
        $this->request = new Request;
        $this->request->url($url)->method($method);
        return $this;
    }
    public function headers(array $headers)
    {
        $this->request->headers($headers);
        return $this;
    }
    public function body(mixed $data)
    {
        $this->request->body($data);
        return $this;
    }
    public function files(array $files)
    {
        $this->request->files($files);
        return $this;
    }
    public function query(array $data)
    {
        $this->request->query($data);
        return $this;
    }

    public function timeout(int $timeout)
    {
        $this->request->timeout($timeout);
        return $this;
    }
    public function verifySSL(bool $verifySSL)
    {
        $this->request->verifySSL($verifySSL);
        return $this;
    }
    public function send()
    {
        return $this->request->send();
    }
}
