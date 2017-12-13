<?php declare(strict_types=1);

namespace Tests\Tsufeki\BlancheJsonRpc;

use PHPUnit\Framework\TestCase;
use Tsufeki\BlancheJsonRpc\Exception\JsonException;
use Tsufeki\BlancheJsonRpc\Json;

/**
 * @covers \Tsufeki\BlancheJsonRpc\Json
 */
class JsonTest extends TestCase
{
    public function test_encodes()
    {
        $this->assertJsonStringEqualsJsonString('[1, 2]', Json::encode([1, 2]));
    }

    public function test_decodes()
    {
        $this->assertSame([1, 2], Json::decode('[1, 2]'));
    }

    public function test_throws_on_decode_error()
    {
        $this->expectException(JsonException::class);
        Json::decode('[1,');
    }

    public function test_throws_on_encode_error()
    {
        $this->expectException(JsonException::class);
        Json::encode(STDOUT);
    }
}
