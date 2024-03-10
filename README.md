# Instructor for PHP

Structured data extraction in PHP, powered by LLMs. Designed for simplicity, transparency, and control.


## What is Instructor?

Instructor is a library that allows you to extract structured, validated data from unstructured text or OpenAI style chat sequence arrays. It is powered by Large Language Models (LLMs).

Instructor for PHP is inspired by the [Instructor](https://jxnl.github.io/instructor/) library for Python created by [Jason Liu](https://twitter.com/jxnlco).


## Instructor in Other Languages

Check out implementations in other languages below:

- [Python](https://www.github.com/jxnl/instructor) (original)
- [Javascript](https://github.com/instructor-ai/instructor-js) (port)
- [Elixir](https://github.com/thmsmlr/instructor_ex/) (port)

If you want to port Instructor to another language, please reach out to us on [Twitter](https://twitter.com/jxnlco) we'd love to help you get started!


## How Instructor Enhances Your Workflow

Instructor introduces three key enhancements compared to direct API usage.

### Response Model

You just specify a PHP class to extract data into via the 'magic' of LLM chat completion. And that's it.

Instructor reduces brittleness of the code extracting the information from textual data by leveraging structured LLM responses.

Instructor helps you write simpler, easier to understand code - you no longer have to define lengthy function call definitions or write code for assigning returned JSON into target data objects.

### Validation

Response model generated by LLM can be automatically validated, following set of rules. Currently, Instructor supports only Symfony validation.

You can also provide a context object to use enhanced validator capabilities.

### Max Retries

You can set the number of retry attempts for requests.

Instructor will repeat requests in case of validation error (or API failure) up to the specified number of times.


## Get Started

Installing Instructor is simple. Run following command in your terminal and you're on your way to a smoother data handling experience!

```bash
composer install cognesy/instructor-php
```


## Usage


### Basic example

This is a simple example demonstrating how Instructor retrieves structured information from provided text (or chat message sequence).

Response model class is a plain PHP class with typehints specifying the types of fields of the object.

```php
use Cognesy\Instructor\Instructor;
use OpenAI;

// Step 1: Define target data structure(s)
class Person {
    public string $name;
    public int $age;
}

// Step 2: Provide content to process
$text = "His name is Jason and he is 28 years old.";

// Step 3: Use Instructor to run LLM inference
$person = (new Instructor)->respond(
    messages: [['role' => 'user', 'content' => $text]],
    responseModel: Person::class,
); // default OpenAI client is used, needs .env file with OPENAI_API_KEY

// Step 4: Work with structured response data
assert($person instanceof Person); // true
assert($person->name === 'Jason'); // true
assert($person->age === 28); // true

echo $person->name; // Jason
echo $person->age; // 28

var_dump($person);
// Person {
//     name: "Jason",
//     age: 28
// }    
```
> **NOTE:** Instructor only supports classes / objects as response models. In case you want to extract simple types or enums, you need to wrap them in Scalar adapter - see section below: Extracting Scalar Values.


### Validation

Instructor validates results of LLM response against validation rules specified in your data model.

> For further details on available validation rules, check [Symfony Validation constraints](https://symfony.com/doc/current/validation.html#constraints).

```php
use Symfony\Component\Validator\Constraints as Assert;

class Person {
    public string $name;
    #[Assert\PositiveOrZero]
    public int $age;
}

$text = "His name is Jason, he is -28 years old.";
$person = (new Instructor(llm: $mockLLM))->respond(
    messages: [['role' => 'user', 'content' => $text]],
    responseModel: Person::class,
);

// if the resulting object does not validate, Instructor throws an exception
```


### Max Retries

In case maxRetries parameter is provided and LLM response does not meet validation criteria, Instructor will make subsequent inference attempts until results meet the requirements or maxRetries is reached.

Instructor uses validation errors to inform LLM on the problems identified in the response, so that LLM can try self-correcting in the next attempt.

```php
use Symfony\Component\Validator\Constraints as Assert;

class Person {
    #[Assert\Length(min: 3)]
    public string $name;
    #[Assert\PositiveOrZero]
    public int $age;
}

$text = "His name is JX, aka Jason, he is -28 years old.";
$person = (new Instructor)->respond(
    messages: [['role' => 'user', 'content' => $text]],
    responseModel: Person::class,
    maxRetries: 3,
);

// if all LLM's attempts to self-correct the results fail, Instructor throws an exception
```


## Shortcuts 

### String as Input

You can provide a string instead of an array of messages. This is useful when you want to extract data from a single block of text and want to keep your code simple.

```php
// Usually, you work with sequences of messages:

$value = (new Instructor)->respond(
    messages: [['role' => 'user', 'content' => "His name is Jason, he is 28 years old."]],
    responseModel: Person::class,
);

// ...but if you want to keep it simple, you can just pass a string:

$value = (new Instructor)->respond(
    messages: "His name is Jason, he is 28 years old.",
    responseModel: Person::class,
);
```


### Extracting Scalar Values

Sometimes we just want to get quick results without defining a class for the response model, especially if we're trying to get a straight, simple answer in a form of string, integer, boolean or float. Instructor provides a simplified API for such cases.

```php
use Cognesy\Instructor\Instructor;
use Cognesy\Instructor\Extras\Scalars\Scalar;

$value = (new Instructor)->respond(
    messages: "His name is Jason, he is 28 years old.",
    responseModel: Scalar::integer('age'),
);

var_dump($value);
// int(28)
```

In this example, we're extracting a single integer value from the text. You can also use `Scalar::string()`, `Scalar::boolean()` and `Scalar::float()` to extract other types of values.

### Extracting Enum Values

Additionally, you can use Scalar adapter to extract one of the provided options by using `Scalar::enum()`.

```php
use Cognesy\Instructor\Instructor;
use Cognesy\Instructor\Extras\Scalars\Scalar;

enum ActivityType {
    case Work = 'work';
    case Entertainment = 'entertainment';
    case Sport = 'sport';
    case Other = 'other';
}

$value = (new Instructor)->respond(
    messages: "His name is Jason, he currently plays Doom Eternal.",
    responseModel: Scalar::enum(ActivityType::class, 'activityType'),
);

var_dump($value);
// enum(ActivityType:Entertainment)
```


## Specifying Data Model

### Type Hints

Use PHP type hints to specify the type of extracted data.

> Use nullable types to indicate that given field is optional.

```php
    class Person {
        public string $name;
        public ?int $age;
        public Address $address;
    }
```

### DocBlock type hints

You can also use PHP DocBlock style comments to specify the type of extracted data. This is useful when you want to specify property types for LLM, but can't or don't want to enforce type at the code level.

```php
class Person {
    /** @var string */
    public $name;
    /** @var int */
    public $age;
    /** @var Address $address person's address */
    public $address;
}
```

See PHPDoc documentation for more details on [DocBlock website](https://docs.phpdoc.org/3.0/guide/getting-started/what-is-a-docblock.html#what-is-a-docblock).


### Typed Collections / Arrays

PHP currently [does not support generics](https://wiki.php.net/rfc/generics) or typehints to specify array element types.

Use PHP DocBlock style comments to specify the type of array elements.

```php
class Person {
    // ...
}

class Event {
    // ...
    /** @var Person[] list of extracted event participants */
    public array $participants;
    // ...
}
```


### Complex data extraction

Instructor can retrieve complex data structures from text. Your response model can contain nested objects, arrays, and enums.

```php
use Cognesy\Instructor\Instructor;
use OpenAI;

// define a data structures to extract data into
class Person {
    public string $name;
    public int $age;
    public string $profession;
    /** @var Skill[] */
    public array $skills;
}

class Skill {
    public string $name;
    public SkillType $type;
}

enum SkillType {
    case Technical = 'technical';
    case Other = 'other';
}

$text = "Alex is 25 years old software engineer, who knows PHP, Python and can play the guitar.";

$person = (new Instructor)->respond(
    messages: [['role' => 'user', 'content' => $text]],
    responseModel: Person::class,
    client: OpenAI::client($yourApiKey),
); // client is passed explicitly, can specify e.g. different base URL

// data is extracted into an object of given class
assert($person instanceof Person); // true

// you can access object's extracted property values
echo $person->name; // Alex
echo $person->age; // 25
echo $person->profession; // software engineer
echo $person->skills[0]->name; // PHP
echo $person->skills[0]->type; // SkillType::Technical
// ...

var_dump($person);
// Person {
//     name: "Alex",
//     age: 25,
//     profession: "software engineer",
//     skills: [
//         Skill {
//              name: "PHP",
//              type: SkillType::Technical,
//         },
//         Skill {
//              name: "Python",
//              type: SkillType::Technical,
//         },
//         Skill {
//              name: "guitar",
//              type: SkillType::Other
//         },
//     ]
// }
```

## Changing LLM model and options

You can specify model and other options that will be passed to OpenAI / LLM endpoint.

For more details on options available - see [OpenAI PHP client](https://github.com/openai-php/client).

```php
$person = (new Instructor)->respond(
    messages: [['role' => 'user', 'content' => $text]],
    responseModel: Person::class,
    model: 'gpt-3.5-turbo',
    options: ['temperature' => 0.0],
    client: OpenAI::client($yourApiKey),
);
// client is passed explicitly
// you can specify e.g. different base URL
```
> Some open source LLMs support OpenAI API, so you can use them with Instructor by specifying appropriate ```model``` and ```base URI``` via ```options``` parameter.


## Using DocBlocks as Additional Instructions for LLM

You can use PHP DocBlocks (/** */) to provide additional instructions for LLM at class or field level, for example to clarify what you expect or how LLM should process your data.

Instructor extracts PHP DocBlocks comments from class and property defined and includes them in specification of response model sent to LLM.

Using PHP DocBlocks instructions is not required, but sometimes you may want to clarify your intentions to improve LLM's inference results.

```php
/**
 * Represents a skill of a person and context in which it was mentioned. 
 */
class Skill {
    public string $name;
    /** @var SkillType $type type of the skill, derived from the description and context */
    public SkillType $type;
    /** Directly quoted, full sentence mentioning person's skill */
    public string $context;
}
```

## Customizing Validation

### ValidationMixin

You can use ValidationMixin trait to add ability of easy, custom data object validation.

```php
use Cognesy\Instructor\Traits\ValidationMixin

class User {
    use ValidationMixin;

    public int $age;
    public int $name;

    public function validate() : array {
        if ($this->age < 18) {
            return ["User has to be adult to sign the contract."];
        }
        return [];
    }
}
```

### Validation Callback

Instructor uses Symfony validation component to validate extracted data. You can use #[Assert/Callback] annotation to build fully customized validation logic.

```php
use Cognesy\Instructor\Instructor;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class UserDetails
{
    public string $name;
    public int $age;
    
    #[Assert\Callback]
    public function validateName(ExecutionContextInterface $context, mixed $payload) {
        if ($this->name !== strtoupper($this->name)) {
            $context->buildViolation("Name must be in uppercase.")
                ->atPath('name')
                ->setInvalidValue($this->name)
                ->addViolation();
        }
    }
}

$user = (new Instructor)->respond(
    messages: [['role' => 'user', 'content' => 'jason is 25 years old']],
    responseModel: UserDetails::class,
    maxRetries: 2
);

assert($user->name === "JASON");
```

See [Symfony docs](https://symfony.com/doc/current/reference/constraints/Callback.html) for more details on how to use Callback constraint.


## Internals

### Lifecycle

As Instructor for PHP processes your request, it goes through several stages:

 1. Initialize and self-configure (with possible overrides defined by developer).
 2. Analyze classes and properties of the response data model specified by developer.
 3. Encode data model into a schema that can be provided to LLM.
 4. Execute request to LLM using specified messages (content) and response model metadata.
 5. Receive a response from LLM or multiple partial responses (if streaming enabled).
 6. Deserialize response received from LLM into originally requested classes and their properties.
 7. In case response contained incomplete or corrupted data - if errors are encountered, create feedback message for LLM and requests regeneration of the response.
 8. Execute validations defined by developer for the data model - if any of them fail, create feedback message for LLM and requests regeneration of the response.
 9. Repeat the steps 4-8, unless specified limit of retries has been reached or response passes validation


### Receiving notification on internal events

Instructor allows you to receive a detailed information at every stage of request and response processing via events.

 * `(new Instructor)->onEvent(string $class, callable $callback)` method - receive callback when specified type of event is dispatched
 * `(new Instructor)->wiretap(callable $callback)` method - receive any event dispatched by Instructor, may be useful for debugging or performance analysis
 * `(new Instructor)->onError(callable $callback)` method - receive callback on any uncaught error, so you can customize handling it, for example logging the error or using some fallback mechanism in an attempt to recover

Receiving events can help you to monitor the execution process and makes it easier for a developer to understand and resolve any processing issues.

```php
$instructor = (new Instructor)
    // see requests to LLM
    ->onEvent(RequestSentToLLM::class, fn($e) => dump($e))
    // see responses from LLM
    ->onEvent(ResponseReceivedFromLLM::class, fn($event) => dump($event))
    // see all events in console-friendly format
    ->wiretap(fn($event) => dump($event->toConsole()))
    // log errors via your custom logger
    ->onError(fn($request, $error) => $logger->log($error));

$instructor->respond(
    messages: "What is the population of Paris?",
    responseModel: Scalar::integer(),
);
// check your console for the details on the Instructor execution
```


## Additional Notes

PHP ecosystem does not (yet) have a strong equivalent of [Pydantic](https://pydantic.dev/), which is at the core of Instructor for Python.

To provide an essential functionality we needed here Instructor for PHP leverages:
- base capabilities of [PHP type system](https://www.php.net/manual/en/language.types.type-system.php),
- [PHP reflection](https://www.php.net/manual/en/book.reflection.php),
- [PHP DocBlock](https://docs.phpdoc.org/2.9/references/phpdoc/index.html) type hinting conventions,
- [Symfony](https://symfony.com/doc/current/index.html) serialization and validation capabilities

Currently, Instructor for PHP works with [OpenAI API](https://platform.openai.com/docs/), but support for other models capable of function calling may be added in the future.


## Dependencies

Instructor for PHP is compatible with PHP 8.2 or later and, due to minimal dependencies, should work with any framework of your choice.

- [OpenAI PHP client](https://github.com/openai-php/client) - for communication with OpenAI API
- [Symfony components](https://symfony.com/) - for validation, serialization and other utilities
- [Jasny PHP DocBlock Parser](https://www.jasny.net/phpdoc-parser/) - for parsing PHP DocBlocks


## TODOs

- [ ] Support for iterables / collections (via ArrayAccess, Iterator)
- [ ] Async
- [ ] Document creation of custom serializers, validators and LLMs
- [ ] Open source LLM support
- [ ] Public vs protected / private fields - document behavior


## Contributing

If you want to help, check out some of the issues. They could be anything from code improvements, a guest blog post, or a new cookbook.


## License

This project is licensed under the terms of the MIT License.
