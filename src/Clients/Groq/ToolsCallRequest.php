<?php
namespace Cognesy\Instructor\Clients\Groq;

use Cognesy\Instructor\ApiClient\Requests\ApiToolsCallRequest;
use Cognesy\Instructor\ApiClient\Responses\ApiResponse;
use Cognesy\Instructor\ApiClient\Responses\PartialApiResponse;
use Cognesy\Instructor\Utils\Json;
use Saloon\Http\Response;

class ToolsCallRequest extends ApiToolsCallRequest
{
    protected string $endpoint = '/chat/completions';

    protected function getToolChoice(): string|array {
        return $this->toolChoice ?: 'auto';
    }

    public function toApiResponse(Response $response): ApiResponse {
        $decoded = Json::parse($response->body());
        $content = $decoded['choices'][0]['message']['tool_calls'][0]['function']['arguments'] ?? '';
        $functionName = $decoded['choices'][0]['message']['tool_calls'][0]['function']['name'] ?? '';
        $finishReason = $decoded['choices'][0]['finish_reason'] ?? '';
        return new ApiResponse(
            content: $content,
            responseData: $decoded,
            functionName: $functionName,
            finishReason: $finishReason,
            toolCalls: null
        );
    }

    public function toPartialApiResponse(string $partialData) : PartialApiResponse {
        $decoded = Json::parse($partialData, default: []);
        $functionName = $decoded['choices'][0]['delta']['tool_calls'][0]['function']['name'] ?? '';
        $argumentsJson = $decoded['choices'][0]['delta']['tool_calls'][0]['function']['arguments'] ?? '';
        return new PartialApiResponse($argumentsJson, $decoded, $functionName);
    }
}