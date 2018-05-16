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
