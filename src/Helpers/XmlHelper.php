<?php

namespace LeKoala\Base\Helpers;

use DOMDocument;
use SilverStripe\Assets\FileNameFilter;

/**
 */
class XmlHelper
{

    /**
     * Renders pretty xml to a string
     *
     * @param string $xml
     * @return string
     */
    public static function beautifyXml($xml)
    {
        if (!$xml) {
            return '';
        }
        $domxml = new DOMDocument('1.0');
        $domxml->preserveWhiteSpace = false;
        $domxml->formatOutput = true;
        $domxml->loadXML($xml);
        return trim($domxml->saveXML());
    }

    /**
     * Xml to array
     *
     * Attributes are stored in @@attributes node
     *
     * @param string $xml
     * @return array
     */
    public static function toArray($xml)
    {
        $sxml = simplexml_load_string($xml);
        $json = json_encode($sxml);
        return json_decode($json, true);
    }

    /**
     * @link link https://stackoverflow.com/questions/8218230/php-domdocument-loadhtml-not-encoding-utf-8-correctly
     * @param string $source
     * @param bool $convertToUtf8
     * @return DOMDocument
     */
    public static function loadDomDocument($source, $convertToUtf8 = true)
    {
        if ($convertToUtf8) {
            $dom = new DomDocument('1.0', 'UTF-8');
            $dom->loadHTML(mb_convert_encoding($source, 'HTML-ENTITIES', 'UTF-8'));
        } else {
            $dom = new DOMDocument();
            $dom->loadHTML('<?xml encoding="utf-8" ?>' . $source);
        }
        return $dom;
    }

    /**
     * Replace a div by ID in a given html
     *
     * @param string $xml
     * @param string $nodeId
     * @param string $content
     * @return string
     */
    public static function replaceNode($xml, $nodeId, $content)
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $doc->loadHTML($xml);
        $contentNode = $doc->getElementById($nodeId);

        $newContent = '<div id="' . $nodeId . '">' . $content . '</div>';
        $newDoc = self::loadDomDocument($newContent);
        $newNode = $doc->importNode($newDoc->documentElement, true);

        // Replace
        $contentNode->parentNode->replaceChild($newNode, $contentNode);

        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;
        $html = $doc->saveHTML($doc->documentElement);
        return $html;
    }

    /**
     * Output headers suitable for xml
     *
     * @param string $title
     * @return void
     */
    public static function outputHeaders($title = null)
    {
        if ($title === null) {
            $title = time();
        } else {
            $filter = new FileNameFilter;
            $title = $filter->filter($title);
        }

        $title = $title . '.xml';

        if (!headers_sent()) {
            header('Content-Type: text/xml');
            header('Content-Disposition: attachment;filename="' . $title . '"');
            header('Cache-Control: max-age=0');
            // If you're serving to IE 9, then the following may be needed
            header('Cache-Control: max-age=1');

            // If you're serving to IE over SSL, then the following may be needed
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
            header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
            header('Pragma: public'); // HTTP/1.0
        }
    }
}
