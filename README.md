# MarketforceInfo/AzureTranslator

[![Code Checks](https://img.shields.io/github/actions/workflow/status/marketforce-info/azure-translator/code-checks.yml?branch=main&logo=github)](https://github.com/marketforce-info/azure-translator/actions/workflows/code-checks.yml)
[![Latest Stable Version](https://img.shields.io/github/v/release/marketforce-info/azure-translator?logo=packagist)](https://github.com/marketforce-info/azure-translator/releases)
[![Total Downloads](https://img.shields.io/packagist/dt/marketforce-info/azure-translator?logo=packagist)](https://packagist.org/packages/marketforce-info/azure-translator)
![Licence](https://img.shields.io/github/license/marketforce-info/azure-translator.svg)

## Description
Sends batches of messages to be translated to the Azure translate service. Has the option of handling variables as a
formatted message.

---

### Installation

```bash
$ composer require marketforce-info/azure-translator
```

### Requirements

* Access to Azure authentication details. Supports subscription key and authorization token mechanisms.
* Depends on a concrete implementation of psr/http-client

## Usage

```php
    use \MarketforceInfo\AzureTranslator\Translator;
    (new Translator())
        ->onTranslate(static function (string $message, Language $language, Context $context) {
            // do something with the message
        })
        ->session(static function ($translator) use ($messages) {
            foreach ($messages as $message) {
                $translator->translate($message);
            }
        });
```

## Features

### Message Format

Allows for basic syntax substitution in messages. For example

```text
Welcome {name} to the monthly newsletter from {company}
```

The formatter will substitute the variables, so they won't be translated and then replace them in the final translated
message.

```php
    use \MarketforceInfo\AzureTranslator\MessageFormatter\BasicFormatter;
    $translator->setMessageFormatter(new BasicFormatter());
```

Basic formatter defaults to `{}` style syntax but this can be changed in the constructor.

### ICU Message Format

Requires an additional component which parses the ICU message format so that a representation can be sent to the
translation service.

#### Installation

```bash
$ composer require marketforce-info/message-format-parser
```

#### Usage

```php
    use \MarketforceInfo\AzureTranslator\MessageFormatter\IcuFormatter;
    $translator->setMessageFormatter(new IcuFormatter());
```

#### Caveat

The process of creating a representation to send to the translation service produces a verbose output which
could have a detrimental effect on the character count and subsequently the billing by the Azure service. Additionally,
the ICU parsing attempts to achieve the best translation outcome by composing variations of the messages when the
format of a ICU message is `select`, `selectordinal` or `plural`.

### Custom Message Format

It's possible to create a custom message format class. The `setMessageFormatter` method accepts any implementation of
the `MessageFormatter` interface.

### Context and State

The `onTranslate` callback method receives three parameters.

`$message` is a string of the translated message.

`$language` is an Enum of the language the message has been translated into.

`$context` is a `Context` object representing different attributes that might be useful. These include the original
message, the language of the original message, a trace ID used to track requests (see below for more information) and
an array of state.

The state is something that can be optionally set at the point of request.

```php
foreach ($messages as $messageId => $message) {
    $translator->translate($message, ['messageId' => $messageId]);
}
```

Then later, in the `onTranslate` callback.

```php
static function (string $message, Language $language, Context $context) use ($db) {
    $db->replace(
        table: 'translations',
        where: ['id' => $context->state['messageId'], 'language' => $language->value],
        data: ['message' => $message]
    );
};
```

### Tracing Requests

includign reusing original generate function

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

## Custom Message Format

Erm...

## Contributions

Contributions gratefully accepted in the form issues or PRs.

## Security

If you discover any security related issues, please email appsupport_uk@marketforce.com instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
