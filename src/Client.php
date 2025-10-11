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
    public function body(mixed $data){
        $this->request->body($data);
        return $this;
    }
    public function files(array $files){
        $this->request->files($files);
        return $this;
    }
    public function send()
    {
       return $this->request->send();
    }
}
