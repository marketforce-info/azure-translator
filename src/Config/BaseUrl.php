<?php

namespace MarketforceInfo\AzureTranslator\Config;

enum BaseUrl: string
{
    case global = 'https://api.cognitive.microsofttranslator.com';
    case asia = 'api-apc.cognitive.microsofttranslator.com';
    case europe = 'api-eur.cognitive.microsofttranslator.com';
    case america = 'api-nam.cognitive.microsofttranslator.com';
}
