<?php

namespace LeKoala\Base\Forms;

use SilverStripe\Forms\LiteralField;

/**
 */
class LiteralFixedField extends LiteralField
{
    protected $locked;

    public function setValue($content, $data = null)
    {
        if ($this->locked) {
            return $this;
        }
        return parent::setValue($content, $data);
    }

    /**
     * Get the value of locked
     * @return bool
     */
    public function getLocked()
    {
        return $this->locked;
    }

    /**
     * Set the value of locked
     *
     * @param bool $locked
     * @return $this
     */
    public function setLocked($locked)
    {
        $this->locked = $locked;
        return $this;
    }
}
