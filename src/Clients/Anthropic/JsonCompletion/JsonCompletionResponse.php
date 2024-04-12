<?php
namespace Cognesy\Instructor\Clients\Anthropic\JsonCompletion;

use Cognesy\Instructor\ApiClient\Data\Responses\ApiResponse;
use Cognesy\Instructor\Utils\Json;
use Saloon\Http\Response;

class JsonCompletionResponse extends ApiResponse
{
    public static function fromResponse(Response $response): self {
        $decoded = Json::parse($response->body());
        $content = $decoded['content'][0]['text'] ?? '';
        return new self($content, $decoded, '');
    }
}