<?php
namespace LeKoala\Base\Forms;

use SilverStripe\Forms\OptionsetField;

/**
 * Helps dealing with Yes/No values (no being tricky since = 0, may be seen as empty)
 */
class YesNoOptionsetField extends OptionsetField
{
    /**
     * @param string $name The field name
     * @param string $title The field title
     * @param array|ArrayAccess $source A map of the dropdown items
     * @param mixed $value The current value
     */
    public function __construct($name, $title = null, $source = array(), $value = null)
    {
        $source = [
            'YES' => _t('Global.YES', 'Yes'),
            'NO' => _t('Global.NO', 'No'),
        ];
        $this->setSource($source);
        if (!isset($title)) {
            $title = $name;
        }
        parent::__construct($name, $title, $source, $value);
        $this->addExtraClass('inline');
    }

    public function Type()
    {
        return 'optionset';
    }

    public function dataValue()
    {
        $value = $this->value;
        if ($value == 'YES') {
            return 1;
        }
        if ($value == 'NO') {
            return 0;
        }
    }

    public function setValue($value, $data = null)
    {
        if ($value) {
            $value = 'YES';
        } elseif (strlen($value) || $value === false) {
            $value = 'NO';
        }
        return parent::setValue($value, $data);
    }
}
