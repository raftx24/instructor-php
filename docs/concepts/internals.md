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

### Response Models

Instructor is able to process several types of input provided as response model, giving you more flexibility on how you interact with the library.

The signature of `respond()` method of Instructor states the `responseModel` can be either string, object or array.

#### Handling string $responseModel value

If `string` value is provided, it is used as a name of the class of the response model.

Instructor checks if the class exists and analyzes the class & properties type information & doc comments to generate a schema needed to specify LLM response constraints.

The best way to provided the name of the response model class is to use `NameOfTheClass::class`, making it easy for IDE to check the type, handle refactorings, etc.


#### Handling object $responseModel value

If `object` value is provided, it is considered an instance of the response model. Instructor checks the class of the instance, then analyzes it and its property type data to specify LLM response constraints.


#### Handling array $responseModel value

If `array` value is provided, it is considered a raw JSON Schema, therefore allowing Instructor to use it directly in LLM requests (after wrapping in appropriate context - e.g. function call).

Instructor requires information on the class of each nested object in your JSON Schema, so it can correctly deserialize the data into appropriate type.

This information is available to Instructor when you are passing $responseModel as a class name or an instance, but it is missing from raw JSON Schema.

Current design useses JSON Schema `comment` field on property to overcome this. Instructor expects developer to use `comment` field to provide fully qualified name of the target class to be used to deserialize property data of object or enum type.


### Response model contracts

Instructor allows you to customize processing of $responseModel value also by looking at the interfaces the class or instance implements:

 - `CanProvideSchema` - implement to be able to provide custom schema, instead of default approach of Instructor analyzing $responseModel value class
 - `CanDeserializeResponse` - implement to customize the way the response from LLM is deserialized from JSON into PHP object, 
 - `CanSelfValidate` - implement to customize the way the deserialized object is validated,
 - `CanTransformResponse` - implement to transform the validated object into target value received by the caller (eg. unwrap simple type from a class to scalar value

For a practical example of using those contracts to customize Instructor processing flow see: src/Extras/Scalars/. It contains an implementation of scalar value response support with a wrapper class implementing custom schema provider, deserialization, validation and transformation into requested value type.