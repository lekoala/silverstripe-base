<?php
namespace LeKoala\Base\Forms;

use SilverStripe\Forms\LiteralField;

/**
 *
 */
class AlertField extends LiteralField
{

    /**
     * @var bool
     */
    protected $allowHTML = true;

    /**
     * @var string
     */
    protected $alertType = 'info';

    /**
     * @param string $name
     * @param string|FormField $content
     * @param string $type
     */
    public function __construct($name, $content = null, $type = null)
    {
        $this->setContent($content);

        parent::__construct($name, $content);

        if ($type) {
            $this->setAlertType($type);
        }
    }

    /**
     * @param array $properties
     *
     * @return string
     */
    public function FieldHolder($properties = array())
    {
        $content = parent::FieldHolder($properties);
        $type = $this->alertType;

        // Wrap in an alert
        $content = "<div class=\"alert alert-$type\" role=\"alert\">$content</div>";

        return $content;
    }

    /**
     * Get the value of alertType
     */
    public function getAlertType()
    {
        return $this->alertType;
    }

    /**
     * Set the value of alertType
     *
     * @return $this
     */
    public function setAlertType($alertType)
    {
        $this->alertType = $alertType;

        return $this;
    }
}
