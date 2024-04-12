<?php
namespace Cognesy\Instructor\Clients\TogetherAI\ToolsCall;

use Cognesy\Instructor\ApiClient\Data\Responses\ApiResponse;
use Cognesy\Instructor\Utils\Json;
use Saloon\Http\Response;

class ToolsCallResponse extends ApiResponse
{
    public static function fromResponse(Response $response): self {
        $decoded = Json::parse($response);
        $content = $decoded['choices'][0]['message']['tool_calls'][0]['function']['arguments'] ?? '';
        $functionName = $decoded['choices'][0]['message']['tool_calls'][0]['function']['name'] ?? '';
        return new self($content, $decoded, $functionName);
    }
}