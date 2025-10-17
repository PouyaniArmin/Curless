<?php

namespace Armin\Curless;

use Exception;

class Response
{
    /**
     * Raw response data including body, headers, status, and info.
     *
     * @var array
     */
    private array $data;

    /**
     * Constructor to initialize response data.
     *
     * @param array $data Raw response array.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get the response body.
     *
     * @return mixed Response body content.
     */
    public function bodyInfo()
    {
        return $this->data['body'];
    }

    /**
     * Get the response headers.
     *
     * @return array Response headers.
     */
    public function headerInfo()
    {
        return $this->data['headers'];
    }

    /**
     * Get the HTTP status code of the response.
     *
     * @return int HTTP status code.
     */
    public function status()
    {
        return $this->data['status'];
    }

    /**
     * Get all raw response data.
     *
     * @return array Full response data array.
     */
    public function info()
    {
        return $this->data;
    }

    /**
     * Decode the response body as JSON.
     *
     * @return mixed Decoded JSON data.
     * @throws Exception If JSON decoding fails.
     */
    public function json()
    {
        $result = json_decode($this->data['body'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $bodyPreview = substr($this->data['body'], 0, 200);
            throw new Exception(
                "JSON decode error: " . json_last_error_msg() . 
                " Status: " . $this->data['status'] . " " . $bodyPreview
            );
        }
        return $result;
    }
}
