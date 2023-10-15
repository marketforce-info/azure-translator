<?php

namespace MarketforceInfo\AzureTranslator\Translator;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

class RequestFactory
{
    public const BASE_URL_GLOBAL = 'https://api.cognitive.microsofttranslator.com';

    public const BASE_URL_ASIA = 'https://api-apc.cognitive.microsofttranslator.com';

    public const BASE_URL_EUROPE = 'https://api-eur.cognitive.microsofttranslator.com';

    public const BASE_URL_US = 'https://api-nam.cognitive.microsofttranslator.com';

    private RequestInterface $baseRequest;

    public function __construct(
        RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        array $toLanguages,
        Language $fromLanguage = Language::english,
        string $baseUrl = self::BASE_URL_GLOBAL,
        array $config = []
    ) {
        $request = $requestFactory
            ->createRequest('POST', $baseUrl)
            ->withHeader('Content-Type', 'application/json');

        $params = [
            'api-version' => '3.0',
            'from' => $fromLanguage->value,
            'textType' => 'html',
            'profanityAction' => 'NoAction',
        ];
        if (isset($config['profanity_handling'])) {
            $params['profanityAction'] = $config['profanity_handling']['action'];
            if ($config['profanity_handling']['action'] === ProfanityHandler::ACTION_MARKED) {
                $params['profanityMarker'] = (is_callable($config['profanity_handling']['marker']))
                    ? ProfanityHandler::MARKER_TAG
                    : ProfanityHandler::MARKER_ASTERISK;
            }
        }

        $uri = $request->getUri();
        $request = $request->withUri(
            $uri->withScheme('https')
                ->withPath('/translate')
                ->withQuery(http_build_query($params) . '&' . Language::asQueryParam($toLanguages))
        );

        if (isset($config['authentication']) && is_array($config['authentication'])) {
            $request = $request->withHeader(...$config['authentication']);
        }
        if (isset($config['subscription_region'])) {
            $request = $request->withHeader('Ocp-Apim-Subscription-Region', $config['subscription_region']);
        }
        if (isset($config['resource_id'])) {
            $request = $request->withHeader('Ocp-Apim-ResourceId', $config['resource_id']);
        }

        $this->baseRequest = $request;
    }

    public function create(Messages $messages, string $clientTraceId): RequestInterface
    {
        $jsonContent = json_encode($messages, JSON_THROW_ON_ERROR);
        return $this->baseRequest
            ->withHeader('X-ClientTraceId', $clientTraceId)
            ->withHeader('Content-Length', (string)strlen($jsonContent))
            ->withBody($this->streamFactory->createStream($jsonContent));
    }
}
