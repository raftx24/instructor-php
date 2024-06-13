<?php

namespace Cognesy\Instructor\Configs\Clients;

use Cognesy\Instructor\ApiClient\Factories\ApiRequestFactory;
use Cognesy\Instructor\ApiClient\ModelParams;
use Cognesy\Instructor\Clients\TogetherAI\TogetherAIClient;
use Cognesy\Instructor\Clients\TogetherAI\TogetherAIConnector;
use Cognesy\Instructor\Configuration\Configuration;
use Cognesy\Instructor\Configuration\Configurator;
use Cognesy\Instructor\Events\EventDispatcher;

class TogetherConfigurator extends Configurator
{
    public function setup(Configuration $config): void {
        $config->declare(
            class: TogetherAIClient::class,
            context: [
                'events' => $config->reference(EventDispatcher::class),
                'connector' => $config->reference(TogetherAIConnector::class),
                'apiRequestFactory' => $config->reference(ApiRequestFactory::class),
                'defaultModel' => 'mistralai/Mixtral-8x7B-Instruct-v0.1',
                'defaultMaxTokens' => 256,
            ],
            getInstance: function($context) {
                $object = new TogetherAIClient(
                    events: $context['events'],
                    connector: $context['connector'],
                );
                $object->withApiRequestFactory($context['apiRequestFactory']);
                $object->defaultModel = $context['defaultModel'];
                $object->defaultMaxTokens = $context['defaultMaxTokens'];
                return $object;
            },
        );

        $config->declare(
            class:TogetherAIConnector::class,
            context: [
                'apiKey' => $_ENV['TOGETHERAI_API_KEY'] ?? '',
                'baseUrl' => $_ENV['TOGETHERAI_BASE_URI'] ?? '',
                'connectTimeout' => 3,
                'requestTimeout' => 30,
                'metadata' => [],
                'senderClass' => '',
            ],
        );

        $config->declare(
            class: ModelParams::class,
            name: 'together:mixtral-8x7b',
            context: [
                'label' => 'Together Mixtral 8x7B',
                'type' => 'mixtral',
                'name' => 'mistralai/Mixtral-8x7B-Instruct-v0.1',
                'maxTokens' => 4096,
                'contextSize' => 4096,
                'inputCost' => 1,
                'outputCost' => 1,
                'roleMap' => [
                    'user' => 'user',
                    'assistant' => 'assistant',
                    'system' => 'system'
                ],
            ],
        );
    }
}