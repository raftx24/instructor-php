# Limiting the length of lists

When dealing with lists of attributes, especially arbitrary properties, it's crucial to manage
the length of list. You can use prompting and enumeration to limit the list length, ensuring
a manageable set of properties.

> To be 100% certain the list does not exceed the limit, add extra
> validation, e.g. using ValidationMixin (see: Validation).

```php
<?php
$loader = require 'vendor/autoload.php';
$loader->add('Cognesy\\Instructor\\', __DIR__.'../../src/');

use Cognesy\Instructor\Instructor;
use Cognesy\Instructor\Traits\ValidationMixin;

class Property
{
    /**  Monotonically increasing ID, not larger than 2 */
    public string $index;
    public string $key;
    public string $value;
}

class UserDetail
{
    use ValidationMixin;

    public int $age;
    public string $name;
    /** @var Property[] List other extracted properties - not more than 2. */
    public array $properties;

    public function validate() : array
    {
        if (count($this->properties) < 3) {
            return [];
        }
        return [[
            'message' => "Number of properties must not more than 2.",
            'path' => 'properties',
            'value' => $this->name
        ]];
    }
}

$text = <<<TEXT
    Jason is 25 years old. He is a programmer. He has a car. He lives in
    a small house in Alamo. He likes to play guitar.
TEXT;

try {
    $user = (new Instructor)->respond(
        messages: [['role' => 'user', 'content' => $text]],
        responseModel: UserDetail::class,
        maxRetries: 0 // change to >0 to reattempt generation in case of validation error
    );

    assert($user->age === 25);
    assert($user->name === "Jason");
    assert(count($user->properties) < 3);
    dump($user);
} catch (\Exception $e) {
    dump("Max retries exceeded\nMessage: {$e->getMessage()}");
}
?>
```