<?php

namespace Cognesy\Instructor\Services;

use Cognesy\Instructor\Schema\Data\Schema\Schema;
use Cognesy\Instructor\Schema\Data\TypeDetails;
use Cognesy\Instructor\Schema\Factories\SchemaBuilder;
use Cognesy\Instructor\Schema\Factories\SchemaFactory;
use Cognesy\Instructor\Schema\Factories\ToolCallBuilder;
use Cognesy\Instructor\Utils\Json;

// TODO: this is part of refactoring in progress - currently not used

class SchemaService
{
    public function __construct(
        protected SchemaFactory $schemaFactory,
        protected ToolCallBuilder $toolCallBuilder,
        protected SchemaBuilder $schemaBuilder,
    ) {}

    public function fromAny(string|TypeDetails $anyInput) : Schema {
        return $this->schemaFactory->schema($anyInput);
    }

    public function fromJsonSchema(array $jsonSchema) : Schema {
        return $this->schemaBuilder->fromArray($jsonSchema);
    }

    public function toJsonSchema(Schema $schema) : string {
        return Json::encode($schema->toArray());
    }

    public function toToolCall(Schema $schema) : array {
        return $this->toolCallBuilder->render($schema->toArray(), $schema->name, $schema->description);
    }
}