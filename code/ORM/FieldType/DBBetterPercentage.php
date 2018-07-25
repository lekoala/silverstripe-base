<?php
namespace LeKoala\Base\ORM\FieldType;

use SilverStripe\ORM\FieldType\DBPercentage;
use LeKoala\Base\Forms\InputMaskPercentageField;

/**
 * Improve percentage field
 */
class DBBetterPercentage extends DBPercentage
{

    /**
     * @param string $title
     * @param array $params
     *
     * @return InputMaskPercentageField
     */
    public function scaffoldFormField($title = null, $params = null)
    {
        $field = new InputMaskPercentageField($this->name, $title);
        return $field;
    }
}
