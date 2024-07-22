<?php
namespace Cognesy\Instructor\Extras\Module\Modules\Text;

//use Cognesy\Instructor\Extras\Module\Core\Module;
use Cognesy\Instructor\Extras\Module\Modules\Prediction;
use Cognesy\Instructor\Extras\Module\Signature\Attributes\ModuleDescription;
use Cognesy\Instructor\Extras\Module\Signature\Attributes\ModuleSignature;

//use Cognesy\Instructor\Extras\Module\Core\Predictor;

#[ModuleSignature('text:string, language:string -> translation:string')]
#[ModuleDescription('Translate the provided text to the target language')]
class Translate extends Prediction
{
//    private Predictor $translate;
//
//    public function __construct() {
//        $this->translate = Predictor::fromSignature(
//            signature: 'text, language -> translation',
//            description: "Translate the provided text to the target language",
//        );
//    }
//
//    public function from(string $text, string $language) : string {
//        return ($this)(text: $text, language: $language)->get('translation');
//    }
//
//    protected function forward(mixed ...$callArgs) : array {
//        $text = $callArgs['text'];
//        $language = $callArgs['language'];
//        $result = $this->translate->predict(text: $text, language: $language);
//        return [
//            'translation' => $result
//        ];
//    }
}
