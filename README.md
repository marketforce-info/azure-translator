# MarketforceInfo/AzureTranslator

[![Code Checks](https://img.shields.io/github/actions/workflow/status/marketforce-info/azure-translator/code-checks.yml?branch=main&logo=github)](https://github.com/marketforce-info/azure-translator/actions/workflows/code-checks.yml)
[![Latest Stable Version](https://img.shields.io/github/v/release/marketforce-info/azure-translator?logo=packagist)](https://github.com/marketforce-info/azure-translator/releases)
[![Total Downloads](https://img.shields.io/packagist/dt/marketforce-info/azure-translator?logo=packagist)](https://packagist.org/packages/marketforce-info/azure-translator)
![Licence](https://img.shields.io/github/license/marketforce-info/azure-translator.svg)

Sends batches of messages to be translated to the Azure translate service. Has the option of handling variables as a
formatted message.

---

## Installation

```bash
$ composer require marketforce-info/azure-translator
```

## Requirements

* Access to Azure authentication details. Supports subscription key and authorization token mechanisms.
* Depends on a concrete implementation of psr/http-client

## Usage

```php
use \MarketforceInfo\AzureTranslator\Builder;
use \MarketforceInfo\AzureTranslator\Translator\Delegate;
use \MarketforceInfo\AzureTranslator\Translator\Language;
use \MarketforceInfo\AzureTranslator\Translator\Translation;
$translator = Builder::createBasic(
    '<subscription-key>',
    [Language::french, Language::italian],
    static function (Translation $translation) {
        // do something with the message
    }
);

$translator->begin(static function (Delegate $translator) use ($messages) {
    foreach ($messages as $message) {
        $translator->translate($message);
    }
});
```

More in depth example of using the `Builder` class.

```php
use \MarketforceInfo\AzureTranslator\Builder;
use \MarketforceInfo\AzureTranslator\MessageFormatter\BasicFormatter;
use \MarketforceInfo\AzureTranslator\Translator\Client;
use \MarketforceInfo\AzureTranslator\Translator\Language;
use \MarketforceInfo\AzureTranslator\Translator\Translation;

$translator = (new Builder())
    ->withBaseUrl(Client::BASE_URL_US)
    ->withLanguages([Language::arabic], Language::english)
    ->withBearerToken('<bearer-token>')
    ->withMessageFormatter(new BasicFormatter('[', ']'))
    ->withTraceIdCallback(fn () => 'xxxx-XXXX-xxxx-XXXX-xxxx')
    ->when(
        $_ENV['delete_profanity'] === true,
        fn (Builder $builder) => $builder->withProfanityDeleted(),
        fn (Builder $builder) => $builder->withProfanityMarked()
    )
    ->withTranslateCallback(function (Translation $translation) {
        // record
    })
    ->create();
```

### Translate Request

Batching of the requested messages is handled automatically. The ability to pass messages to be translated is done via
the `Delegate` class.

```php
$translator->begin(static function (Delegate $translator) use ($untranslatedMessages) {
    foreach ($untranslatedMessages as $message) {
        $translator->translate($message);
    }
});
```

### Translated Message

The `onTranslate` callback method receives a DTO translation of an individual translated message for a language. It
contains four properties:

`$message` is a string of the translated message.

`$language` is an `Language` enum of the language the message has been translated into.

`$traceId` is a trace ID used to track requests (see below for more information).

`$state` is an array that was optionally passed as at the point of the translation request.

The state is something that can be optionally set at the point of request (`translate`).

```php
foreach ($messages as $messageId => $message) {
    $translator->translate($message, ['messageId' => $messageId]);
}
```

Then later, in the `onTranslate` callback.

```php
static function (Translation $translation) use ($db) {
    $db->replace(
        table: 'translations',
        where: [
            'id' => $translation->state['messageId'],
            'language' => $translation->language->value
        ],
        data: ['message' => $translation->message]
    );
};
```

## Features

### Message Format

By default, there is no message formatting. The following outlines alternatives to allow for different translation
behaviours.

#### Basic

Allows for basic syntax substitution in messages. For example

```text
Welcome {name} to the monthly newsletter from {company}
```

The formatter will substitute the variables, so they won't be translated and then replace them in the final translated
message.

```php
use \MarketforceInfo\AzureTranslator\MessageFormatter\BasicFormatter;
$builder->withMessageFormatter(new BasicFormatter());
```

Basic formatter defaults to `{}` style syntax but this can be changed in the constructor.

#### ICU Message Format

Requires an additional component which parses the ICU message format so that a representation can be sent to the
translation service.

#### Installation

```bash
$ composer require marketforce-info/message-format-parser
```

##### Usage

```php
use \MarketforceInfo\AzureTranslator\MessageFormatter\IcuFormatter;
$builder->withMessageFormatter(new IcuFormatter());
```

##### Caveat

The process of creating a representation to send to the translation service produces a verbose output which
could have a detrimental effect on the character count and subsequently the billing by the Azure service. Additionally,
the ICU parsing attempts to achieve the best translation outcome by composing variations of the messages when the
format of a ICU message is `select`, `selectordinal` or `plural`.

#### Custom Message Format

It's possible to create a custom message format class. The `withMessageFormatter` method accepts any implementation of
the `MessageFormatter` interface.

### Tracing Requests

Each individual request to the Azure service includes a Client Trace ID. By default, this is handled automatically.
This can be overridden with the following method.

#### User defined function

```php
$builder->withTraceIdCallback(fn () => 'xxxx-XXXX-xxxx-XXXX-xxxx');
// or
$builder->withTraceIdCallback([$this, 'traceFunction']);
// or
$builder->withTraceIdCallback(fn (\Closure $generatorFn) => $generatorFn);
```

#### Generator function

As shown, the call back is passed a parameter which allows the use of the internal generator function. This is useful
in scenarions where it's a trace ID is recorded for every request made.

### Handling Profanity

Azure has three options for handling profanity. None, deleted or marked with asterisks or tags. The component allows
this to be specified. By default, no profanity handling will be enabled. It allows for a callback to customise the way
profane phrases are displayed.

#### Methods available

```php
$builder->withoutProfanityHandling();
$builder->withProfanityDeleted();
$builder->withProfanityMarked(); // words replaced with *
$builder->withProfanityMarked(static function (string $phrase) {
    return '<span class="profanity">' . str_pad('', mb_strlen($phrase), 'x') . '</span>';
});
```
## Contributions

Contributions gratefully accepted in the form issues or PRs.

## Security

If you discover any security related issues, please email appsupport_uk@marketforce.com instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
