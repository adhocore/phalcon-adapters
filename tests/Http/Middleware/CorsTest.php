<?php

namespace PhalconExt\Test\Http\Middleware;

use Phalcon\Http\Request;
use PhalconExt\Http\Middleware\Cors;
use PhalconExt\Test\WebTestCase;

class CorsTest extends WebTestCase
{
    protected $corsMw;

    public function setUp()
    {
        parent::setUp();

        // $this->configure(['cors' => ['']]);
        $this->configure('cors', [
            'exposedHeaders' => ['X-Cache', 'X-Cache-ID'],
            'allowedMethods' => ['GET', 'GET'], // dont allow POST
        ]);

        ($this->corsMw = new Cors)->boot();    }

    public function test_adds_cors_headers()
    {
        $headers = ['Access-Control-Request-Method' => 'GET', 'Origin' => 'http://127.0.0.1:1234'];

        // Preflight request (OPTIONS)
        $this->doRequest('OPTIONS /corsheader', [], $headers)
            ->assertResponseOk()
            ->assertHeaderKeys(['Access-Control-Allow-Origin', 'Access-Control-Allow-Credentials'])
            ->assertHeaderKeys(['Access-Control-Max-Age', 'Access-Control-Allow-Methods']);

        unset($headers['Access-Control-Request-Method']);

        // Normal request (GET)
        $this->doRequest('/corsheader', [], $headers)
            ->assertResponseOk()
            ->assertResponseJson()
            ->assertHeaderKeys(['Access-Control-Allow-Origin', 'Access-Control-Allow-Credentials'])
            ->assertNotHeaderKeys(['Access-Control-Max-Age', 'Access-Control-Allow-Methods']);
    }

    public function test_preflight_fails()
    {
        $headers = ['Access-Control-Request-Method' => 'GET', 'Origin' => 'http://invalid.origin'];

        $this->doRequest('OPTIONS /corsheader', [], $headers)
            ->assertResponseNotOk()
            ->assertStatusCode(403);

        $headers = ['Access-Control-Request-Method' => 'POST', 'Origin' => 'http://127.0.0.1:1234'];

        $this->doRequest('OPTIONS /corsheader', [], $headers)
            ->assertResponseNotOk()
            ->assertStatusCode(405);

        $headers = ['Access-Control-Request-Method' => 'GET', 'Origin' => 'http://127.0.0.1:1234', 'Access-Control-Request-Headers' => 'X-Something'];

        $this->doRequest('OPTIONS /corsheader', [], $headers)
            ->assertResponseNotOk()
            ->assertStatusCode(403);
    }

    public function test_doesnt_add_cors_headers()
    {
        // Normal request (GET)
        $this->doRequest('/cors', [], [])
            ->assertResponseOk()
            ->assertNotHeaderKeys(['Access-Control-Allow-Origin', 'Access-Control-Allow-Credentials']);
    }
}