<?php
namespace LeKoala\Base\Forms;

use SilverStripe\Forms\FormField;
use SilverStripe\ORM\DataObjectInterface;

/**
 * A base class for fields using json as storage
 * or relations
 */
abstract class JsonFormField extends FormField
{
    public function saveInto(DataObjectInterface $record)
    {
        $fieldname = $this->name;

        $relation = ($fieldname && $record && $record->hasMethod($fieldname)) ? $record->$fieldname() : null;

        $value = $this->dataValue();

        if ($relation) {
            // TODO: Save to relation
        } else {
            if (is_array($value)) {
                $this->value = json_encode(array_values($value));
            }
        }
        parent::saveInto($record);
    }

    public function getAttributes()
    {
        $attrs = parent::getAttributes();
        // unset value otherwise array will cause errors
        unset($attrs['value']);
        return $attrs;
    }

    /**
     * @return string
     */
    public function getValueJson()
    {
        $v = $this->value;
        if (is_array($v)) {
            $v = json_encode($v);
        }
        if (strpos($v, '[') !== 0) {
            return '[]';
        }
        return $v;
    }

    public function setValue($value, $data = null)
    {
        // Allow set raw json as value
        if ($value && is_string($value) && strpos($value, '[') === 0) {
            $value = json_decode($value);
        }
        parent::setValue($value, $data);
    }
}
