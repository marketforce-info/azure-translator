<?php

declare(strict_types=1);

namespace MarketforceInfo\AzureTranslator\Translator;

use MarketforceInfo\AzureTranslator\Exceptions\InvalidArgumentException;

enum Language: string
{
    case afrikaans = 'af';
    case albanian = 'sq';
    case amharic = 'am';
    case arabic = 'ar';
    case armenian = 'hy';
    case assamese = 'as';
    case azerbaijani = 'az';
    case bangla = 'bn';
    case bashkir = 'ba';
    case basque = 'eu';
    case bosnian = 'bs';
    case bulgarian = 'bg';
    case cantonese = 'yue';
    case catalan = 'ca';
    case chineseSimplified = 'zh-Hans';
    case chineseTraditional = 'zh-Hant';
    case croatian = 'hr';
    case czech = 'cs';
    case danish = 'da';
    case dari = 'prs';
    case divehi = 'dv';
    case dutch = 'nl';
    case english = 'en';
    case estonian = 'et';
    case faroese = 'fo';
    case fijian = 'fj';
    case filipino = 'fil';
    case finnish = 'fi';
    case french = 'fr';
    case frenchCanada = 'fr-ca';
    case galician = 'gl';
    case georgian = 'ka';
    case german = 'de';
    case greek = 'el';
    case gujarati = 'gu';
    case haitianCreole = 'ht';
    case hebrew = 'he';
    case hindi = 'hi';
    case hmong_daw = 'mww';
    case hungarian = 'hu';
    case icelandic = 'is';
    case indonesian = 'id';
    case inuinnqtun = 'ikt';
    case inuktitut = 'iu';
    case irish = 'ga';
    case italian = 'it';
    case japanese = 'ja';
    case kannada = 'kn';
    case kazakh = 'kk';
    case khmer = 'km';
    case klingon = 'tlh-Latn';
    case klingonPiqad = 'tlh-Piqd';
    case korean = 'ko';
    case kurdishCentral = 'ku';
    case kurdishNorthern = 'kmr';
    case kyrgyz = 'ky';
    case lao = 'lo';
    case latvian = 'lv';
    case lithuanian = 'lt';
    case masedonian = 'mk';
    case malagasy = 'mg';
    case malay = 'ms';
    case malayalam = 'ml';
    case maltese = 'mt';
    case maori = 'mi';
    case marathi = 'mr';
    case mongolianCyrillic = 'mn-Cyrl';
    case mongolianTraditional = 'mn-Mong';
    case myanmar = 'my';
    case nepali = 'ne';
    case norwegian = 'nb';
    case odia = 'or';
    case pashto = 'ps';
    case persian = 'fa';
    case polish = 'pl';
    case portuguese = 'pt';
    case portuguesePortugal = 'pt-pt';
    case punjabi = 'pa';
    case queretaroOtomi = 'otq';
    case romanian = 'ro';
    case russian = 'ru';
    case samoan = 'sm';
    case serbianCyrillic = 'sr-Cyrl';
    case serbianLatin = 'sr-Latn';
    case slovak = 'sk';
    case slovenian = 'sl';
    case somali = 'so';
    case spanish = 'es';
    case swahili = 'sw';
    case swedish = 'sv';
    case tahitian = 'ty';
    case tamil = 'ta';
    case tatar = 'tt';
    case telugu = 'te';
    case thai = 'th';
    case tibetan = 'bo';
    case tigrinya = 'ti';
    case tongan = 'to';
    case turkish = 'tr';
    case turkmen = 'tk';
    case ukrainian = 'uk';
    case upperSorbian = 'hsb';
    case urdu = 'ur';
    case uyghur = 'ug';
    case uzbek = 'uz';
    case vietnamese = 'vi';
    case welsh = 'cy';
    case yucatecMaya = 'yua';
    case zulu = 'zu';

    /**
     * @param array<int, Language> $languages
     */
    public static function verify(array $languages, Language $exclude): void
    {
        if (empty($languages)) {
            throw new InvalidArgumentException('To languages cannot be empty');
        }
        try {
            $unique = array_unique(array_map(static fn (Language $language) => $language->value, $languages));
        } catch (\TypeError) {
            throw new InvalidArgumentException('List of languages must be an array of Language objects');
        }
        if (count($unique) !== count($languages)) {
            throw new InvalidArgumentException('List of languages must be unique');
        }
        if (in_array($exclude->value, $unique, true)) {
            throw new InvalidArgumentException("List of languages cannot contain '{$exclude->value}'");
        }
    }

    /**
     * @param array<int, Language> $languages
     */
    public static function asQueryParam(array $languages): string
    {
        return implode(
            '&',
            self::toValues($languages, static fn (string $language) => 'to=' . urlencode($language))
        );
    }

    public static function toValues(array $languages, \Closure $transform = null): array
    {
        if (!$transform) {
            $transform = static fn (string $language) => $language;
        }
        return array_map(static fn (Language $language) => $transform($language->value), $languages);
    }
}
