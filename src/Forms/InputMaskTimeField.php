<?php

namespace LeKoala\Base\Forms;

use SilverStripe\ORM\DataObjectInterface;

/**
 * Format time field
 * @link https://github.com/RobinHerbots/Inputmask/blob/5.x/README_date.md
 */
class InputMaskTimeField extends InputMaskDateTimeField
{
    /**
     * Set this to true if internal value is seconds
     *
     * @var boolean
     */
    protected $isNumeric = false;

    public function __construct($name, $title = null, $value = null)
    {
        parent::__construct($name, $title, $value);

        $this->setAlias(self::ALIAS_DATETIME);
        $this->setInputFormat('HH:MM:ss');
    }

    public function setValue($value, $data = null)
    {
        if ($this->isNumeric && is_numeric($value)) {
            $old = $value;
            $value = self::secondsToTime($value);
        }
        // Don't call parent that can set locale formatted date
        $this->value = $value;
        return $this;
    }

    public function dataValue()
    {
        $value = parent::dataValue();
        // Value is stored in database in seconds
        if ($this->isNumeric) {
            return self::timeToSeconds($value);
        }
        return $value;
    }

    public function saveInto(DataObjectInterface $record)
    {
        return parent::saveInto($record);
    }

    /**
     * Get the value of isNumeric
     * @return mixed
     */
    public function getIsNumeric()
    {
        return $this->isNumeric;
    }

    /**
     * Set the value of isNumeric
     *
     * @param mixed $isNumeric
     * @return $this
     */
    public function setIsNumeric($isNumeric)
    {
        $this->isNumeric = $isNumeric;
        return $this;
    }
}
