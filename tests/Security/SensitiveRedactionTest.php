<?php

namespace DreamFactory\Core\Logger\Tests\Security;

use DreamFactory\Core\Logger\Services\BaseService;
use PHPUnit\Framework\TestCase;

/**
 * Security: BaseService must redact credential-bearing headers and fields
 * from log payloads sent to remote aggregators (Logstash/Graylog/etc.).
 */
class SensitiveRedactionTest extends TestCase
{
    public function testAuthorizationHeaderRedacted(): void
    {
        $request = [
            'headers' => [
                'authorization' => 'Bearer abc.def.ghi',
                'host'          => 'localhost',
            ],
        ];
        $out = BaseService::redactSensitiveRequestFields($request);
        $this->assertSame('***REDACTED***', $out['headers']['authorization']);
        $this->assertSame('localhost', $out['headers']['host']);
    }

    public function testApiKeyAndSessionTokenHeaderRedacted(): void
    {
        $request = [
            'headers' => [
                'x-dreamfactory-session-token' => 'eyJ...',
                'x-dreamfactory-api-key'       => 'abc',
                'x-mcp-internal-key'           => 'secret',
                'cookie'                       => 'session=foo',
            ],
        ];
        $out = BaseService::redactSensitiveRequestFields($request);
        foreach (['x-dreamfactory-session-token', 'x-dreamfactory-api-key', 'x-mcp-internal-key', 'cookie'] as $h) {
            $this->assertSame('***REDACTED***', $out['headers'][$h], "Header {$h} should be redacted");
        }
    }

    public function testNestedPayloadCredentialsRedacted(): void
    {
        $request = [
            'payload' => [
                'username'      => 'alice',
                'password'      => 'hunter2',
                'access_token'  => 'tok',
            ],
            'parameters' => [
                'session_token' => 'eyJ...',
                'limit'         => 10,
            ],
        ];
        $out = BaseService::redactSensitiveRequestFields($request);
        $this->assertSame('alice', $out['payload']['username']);
        $this->assertSame('***REDACTED***', $out['payload']['password']);
        $this->assertSame('***REDACTED***', $out['payload']['access_token']);
        $this->assertSame('***REDACTED***', $out['parameters']['session_token']);
        $this->assertSame(10, $out['parameters']['limit']);
    }

    public function testRedactionIsCaseInsensitive(): void
    {
        $request = ['headers' => ['Authorization' => 'Bearer x', 'COOKIE' => 'session=foo']];
        $out = BaseService::redactSensitiveRequestFields($request);
        $this->assertSame('***REDACTED***', $out['headers']['Authorization']);
        $this->assertSame('***REDACTED***', $out['headers']['COOKIE']);
    }
}
