# curless

A simple PHP library to make HTTP requests using cURL in an easy and elegant way. 
It provides classes `Request`, `Response`, and `Client` to simplify sending requests, handling responses, and working with headers, body, files, and query parameters.

## Installation

You can install `curless` via Composer:

```bash
composer require armindev/curless
```

## Usage

### Basic GET Request
```php
use Armin\Curless\Client;

$client = new Client();
$response = $client->request('GET', 'https://api.example.com/search')
                   ->query(['q' => 'curless', 'page' => 1])
                   ->timeout(5)
                   ->send();

print_r($response);
```
### POST Request with JSON Body
```php
use Armin\Curless\Client;

$client = new Client();
$response = $client->request('POST', 'https://api.example.com/create')
                   ->headers(['Content-Type' => 'application/json'])
                   ->body(['name' => 'John', 'age' => 30])
                   ->send();

print_r($response);
```
### Sending Files (multipart/form-data)
```php 
use Armin\Curless\Client;

$client = new Client();
$response = $client->request('POST', 'https://api.example.com/upload')
                   ->files(['file' => '/path/to/file.jpg'])
                   ->send();

print_r($response);
```
### Using Response Class
```php 
use Armin\Curless\Client;

$client = new Client();
$data = $client->request('GET', 'https://api.example.com/data')
               ->send();

$resp = $client->response($data);
echo $resp->getBody();
echo $resp->getStatus();
```
## Classes / API Reference

### `Request`
Handles creating and sending HTTP requests.

**Main Methods:**
- `url(string $url)` – Set the request URL.
- `method(string $method)` – Set HTTP method (GET, POST, PUT, DELETE, etc.)
- `headers(array $headers)` – Set request headers.
- `query(array $query)` – Set query parameters.
- `body(mixed $data)` – Set request body (array, string, etc.)
- `files(array $files)` – Attach files for multipart/form-data.
- `timeout(int $seconds)` – Set timeout in seconds.
- `verifySSL(bool $verify)` – Enable/disable SSL verification.
- `send()` – Execute the request and return response array.

---

### `Response`
Handles the response data from a request.

**Main Methods:**
- `bodyInfo()` – Get the response body.
- `headerInfo()` – Get response headers.
- `status()` – Get HTTP status code.
- `info()` – Get all raw response data.
- `json()` – Decode response body as JSON.

---

### `Client`
Simplifies using `Request` and `Response` together.

**Main Methods:**
- `request(string $method, string $url)` – Initialize a request.
- `headers(array $headers)` – Set request headers.
- `body(mixed $data)` – Set request body.
- `files(array $files)` – Attach files.
- `query(array $data)` – Set query parameters.
- `timeout(int $seconds)` – Set timeout.
- `verifySSL(bool $verify)` – Enable/disable SSL verification.
- `send()` – Send the request and get raw response.
- `response(array $data)` – Wrap raw response into a `Response` object.
- `getBody()` – Get response body from `Response`.
- `getHeaders()` – Get response headers from `Response`.
- `getStatus()` – Get HTTP status code from `Response`.
- `getInfo()` – Get all raw response data from `Response`.
- `json()` – Decode response body as JSON.

## Configuration / Options

`curless` allows customizing requests with the following options:

- **Timeout**  
  Set a custom timeout for the request in seconds.  
  ```php
  $client->timeout(10);
  ```
- **SSL Verification**  
  Enable or disable SSL certificate verification. By default, it is enabled.  
  ```php
  $client->verifySSL(false);
  ```
  - **Headers**  
  Add custom headers to your request.  
  ```php
  $client->headers(['Authorization' => 'Bearer TOKEN']);
  ```
   - **Query Parameters**
Add query parameters for GET requests or append to URL.
```php
$client->query(['page' => 1, 'limit' => 20]);
```
- **Request Body**
Send data in POST, PUT, or PATCH requests. Supports JSON and form data.
```php 
$client->body(['name' => 'John']);
```
- **Files**
Attach files for multipart/form-data requests.
```php
$client->files(['file' => '/path/to/file.jpg']);
```
## License

This project is licensed under the MIT License.  
See the [LICENSE](LICENSE) file for more details.
