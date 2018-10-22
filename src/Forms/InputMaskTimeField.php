<?php
namespace LeKoala\Base\Forms;

use SilverStripe\ORM\DataObjectInterface;

/**
 * Format time field
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

        $this->setInputFormat('HH:MM:ss');
    }

    public function setValue($value, $data = null)
    {
        if ($this->isNumeric && is_numeric($value)) {
            $value = self::secondsToTime($value);
        }
        return parent::setValue($value, $data);
    }

    public function dataValue()
    {
        $val = parent::dataValue();
        if ($this->isNumeric) {
            return self::timeToSeconds($val);
        }
        return $val;
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
