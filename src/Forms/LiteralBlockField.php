<?php
namespace LeKoala\Base\Forms;

use SilverStripe\Forms\LiteralField;

/**
 * Wrap the literal field in a regular div with field class assigned
 */
class LiteralBlockField extends LiteralField
{
    /**
     * @param array $properties
     *
     * @return string
     */
    public function FieldHolder($properties = array())
    {
        $content = parent::FieldHolder($properties);

        $content = '<div class="field literal">' . $content . '</div>';

        return $content;
    }
}
