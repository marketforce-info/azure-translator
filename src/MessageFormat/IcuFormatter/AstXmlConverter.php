<?php

namespace MarketforceInfo\AzureTranslator\MessageFormat\IcuFormatter;
use MarketforceInfo\AzureTranslator\MessageFormat\DOMDocument;

class AstXmlConverter
{

    public function toXml($astRoot)
    {

        $dom = new DOMDocument('1.0', 'UTF-8');
        $root = $dom->createElement('messagepattern');
        $dom->appendChild($root);

        $this->convertNode($astRoot, $dom, $root);

        return $dom->saveXML();

    }

    private function convertNode($astNode, $dom, $parent)
    {

        $node = $dom->createElement($astNode->type);
        $parent->appendChild($node);

        if ($astNode->type === 'text') {
            $text = $dom->createTextNode($astNode->value);
            $node->appendChild($text);
        } else if ($astNode->type === 'argument') {
            $node->setAttribute('name', $astNode->name);
            if ($astNode->format) {
                $node->setAttribute('format', $astNode->format);
            }
        } else if ($astNode->type === 'literal') {
            $text = $dom->createTextNode($astNode->value);
            $node->appendChild($text);
        }

        if (isset($astNode->children)) {
            foreach ($astNode->children as $childNode) {
                $this->convertNode($childNode, $dom, $node);
            }
        }

    }

}
