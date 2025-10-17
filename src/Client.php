<?php

namespace Armin\Curless;

use CurlHandle;

class Client
{
    protected Request $request;
    protected Response $response;
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

    public function response(array $data)
    {
        $this->response = new Response($data);
        return $this;
    }
    public function getBody()
    {
        return $this->response->bodyInfo();
    }

    public function getHeaders()
    {
        return $this->response->headerInfo();
    }
    public function getStatus()
    {
        return $this->response->status();
    }
    
    public function getInfo()
    {
        return $this->response->info();
    }
    public function json(){
        return $this->response->json();
    }
}
