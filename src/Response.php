<?php

namespace Armin\Curless;

use Exception;

class Response
{
    private array $data;
    public function __construct(array $data)
    {
        $this->data = $data;
    }
    public function bodyInfo()
    {
        return $this->data['body'];
    }
    public function headerInfo()
    {
        return $this->data['headers'];
    }
    public function status()
    {
        return $this->data['status'];
    }
    public function info()
    {
        return $this->data;
    }
    public function json()
    {
        $result = json_decode($this->data['body'],true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $bodyPreview=substr($this->data['body'],0,200);
            throw new Exception("JSON decode error:".json_last_error_msg()." Status: ".$this->data['status']." $bodyPreview");
        }
        return $result;
    }
}
