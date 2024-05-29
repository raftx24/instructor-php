<?php

namespace Cognesy\Instructor\Schema\Factories;

use Cognesy\Instructor\Schema\Data\Schema\ArraySchema;
use Cognesy\Instructor\Schema\Data\Schema\EnumSchema;
use Cognesy\Instructor\Schema\Data\Schema\ObjectRefSchema;
use Cognesy\Instructor\Schema\Data\Schema\ObjectSchema;
use Cognesy\Instructor\Schema\Data\Schema\ScalarSchema;
use Cognesy\Instructor\Schema\Data\Schema\Schema;
use Cognesy\Instructor\Schema\Data\TypeDetails;
use Cognesy\Instructor\Schema\PropertyMap;
use Cognesy\Instructor\Schema\SchemaMap;
use Cognesy\Instructor\Schema\Utils\ClassInfo;

/**
 * Factory for creating schema objects from class names
 *
 * NOTE: Currently, OpenAI models do not comprehend well object references for
 * complex structures, so it's safer to return the full object schema with all
 * properties inlined.
 */
class SchemaFactory
{
    /** @var bool switches schema rendering between inlined or referenced object properties */
    protected bool $useObjectReferences;

    protected SchemaMap $schemaMap;
    protected PropertyMap $propertyMap;
    protected TypeDetailsFactory $typeDetailsFactory;

    public function __construct(
        bool $useObjectReferences = false,
    ) {
        $this->useObjectReferences = $useObjectReferences;
        //
        $this->schemaMap = new SchemaMap;
        $this->propertyMap = new PropertyMap;
        $this->typeDetailsFactory = new TypeDetailsFactory;
    }

    /**
     * Extracts the schema from a class and constructs a function call
     *
     * @param string $anyType - class name, enum name or type name string OR TypeDetails object OR any object instance
     */
    public function schema(string|object $anyType) : Schema {
        $type = match(true) {
            $anyType instanceof TypeDetails => $anyType,
            is_string($anyType) => $this->typeDetailsFactory->fromTypeName($anyType),
            is_object($anyType) => $this->typeDetailsFactory->fromTypeName(get_class($anyType)),
            default => throw new \Exception('Unknown input type: '.gettype($anyType)),
        };
        $typeString = (string) $type;
        if (!$this->schemaMap->has($type)) {
            $this->schemaMap->register($typeString, $this->makeSchema($type));
        }
        return $this->schemaMap->get($anyType);
    }

    /**
     * Makes schema for properties
     *
     * @param TypeDetails $type
     * @param string $name
     * @param string $description
     * @return \Cognesy\Instructor\Schema\Data\Schema\Schema
     */
    public function makePropertySchema(TypeDetails $type, string $name, string $description): Schema {
        $match = match (true) {
            ($type->type == TypeDetails::PHP_OBJECT) => $this->makePropertyObject($type, $name, $description),
            ($type->type == TypeDetails::PHP_ENUM) => new EnumSchema($type, $name, $description),
            ($type->type == TypeDetails::PHP_ARRAY) => new ArraySchema(
                $type,
                $name,
                $description,
                $this->makePropertySchema($type->nestedType, 'item', 'Correctly extract items of type: '.$type->nestedType->shortName()),
            ),
            in_array($type->type, TypeDetails::PHP_SCALAR_TYPES) => new ScalarSchema($type, $name, $description),
            default => throw new \Exception('Unknown type: ' . $type->type),
        };
        return $match;
    }

    // INTERNAL //////////////////////////////////////////////////////////////////////////////////

    /**
     * Extracts the schema from a property of a class
     *
     * @param string $class
     * @param string $property
     */
    protected function property(string $class, string $property) : Schema {
        if (!$this->propertyMap->has($class, $property)) {
            $this->propertyMap->register($class, $property, $this->getPropertySchema($class, $property));
        }
        return $this->propertyMap->get($class, $property);
    }

    /**
     * Gets all the property schemas of a class
     *
     * @param string $class
     * @return Schema[]
     */
    protected function getPropertySchemas(string $class) : array {
        $classInfo = new ClassInfo($class);
        $properties = $classInfo->getProperties();
        $propertySchemas = [];
        foreach ($properties as $propertyName => $propertyInfo) {
            if (!$propertyInfo->isPublic()) {
                continue;
            }
            $propertySchemas[$propertyName] = $this->property($class, $propertyName);
        }
        return $propertySchemas;
    }

    /**
     * Gets the schema of a property
     *
     * @param string $class
     * @param string $property
     * @return Schema
     */
    protected function getPropertySchema(string $class, string $property) : Schema {
        $propertyInfoType = (new ClassInfo($class))->getType($property);
        $type = $this->typeDetailsFactory->fromPropertyInfo($propertyInfoType);
        $description = $this->getPropertyDescription($type, $class, $property);
        return $this->makePropertySchema($type, $property, $description);
    }

    /**
     * Gets full property description
     *
     * @param TypeDetails $type
     * @param string $class
     * @param string $property
     * @return string
     */
    protected function getPropertyDescription(TypeDetails $type, string $class, string $property) : string {
        if (in_array($type->type, TypeDetails::PHP_OBJECT_TYPES)) {
            $classDescription = (new ClassInfo($type->class))->getClassDescription();
        } else {
            $classDescription = '';
        }
        return implode("\n", array_filter([
            (new ClassInfo($class))->getPropertyDescription($property),
            $classDescription,
        ]));
    }

    /**
     * Makes schema for top level item (depending on the type)
     *
     * @param TypeDetails $type
     * @param string $name
     * @param string $description
     * @return Schema
     */
    protected function makeSchema(TypeDetails $type) : Schema {
        $classInfo = new ClassInfo($type->class);
        return match (true) {
            ($type->type == TypeDetails::PHP_OBJECT) => new ObjectSchema(
                $type,
                $type->classOnly(),
                $classInfo->getClassDescription(),
                $this->getPropertySchemas($type->class),
                $classInfo->getRequiredProperties(),
            ),
            ($type->type == TypeDetails::PHP_ENUM) => new EnumSchema(
                $type,
                $type->class,
                $classInfo->getClassDescription(),
            ),
            ($type->type == TypeDetails::PHP_ARRAY) => new ArraySchema(
                $type,
                '',
                '',
                $this->makePropertySchema($type, 'item', 'Correctly extract items of type: '.$type->nestedType->shortName())
            ),
            in_array($type->type, TypeDetails::PHP_SCALAR_TYPES) => new ScalarSchema($type, 'value', 'Correctly extracted value'),
            default => throw new \Exception('Unknown type: '.$type->type),
        };
    }

    /**
     * Makes schema for object properties
     *
     * @param TypeDetails $type
     * @param string $name
     * @param string $description
     * @return Schema
     */
    protected function makePropertyObject(TypeDetails $type, string $name, string $description): Schema {
        if ($this->useObjectReferences) {
            return new ObjectRefSchema($type, $name, $description);
        }
        return new ObjectSchema(
            $type,
            $name,
            $description,
            $this->getPropertySchemas($type->class),
            (new ClassInfo($type->class))->getRequiredProperties(),
        );
    }
}