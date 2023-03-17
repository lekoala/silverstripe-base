<?php

namespace LeKoala\Base\Forms;

use SilverStripe\Forms\LiteralField;

/**
 * Bootstrap alert
 * Maps good/bad classes
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
     * @var bool
     */
    protected $messageClass = null;

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
        $extraClasses = $this->extraClasses;
        if (is_array($extraClasses)) {
            $extraClasses = implode(" ", $extraClasses);
        }
        if ($extraClasses) {
            $extraClasses = " " . $extraClasses;
        }
        $classes = 'alert alert-' . $type . '' . $extraClasses;
        if ($this->messageClass) {
            $classes .= " message " . $this->messageClass;
        }
        $content = "<div class=\"{$classes}\" role=\"alert\">$content</div>";

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
        switch ($alertType) {
            case 'bad':
                $this->messageClass = $alertType;
                $alertType = "danger";
                break;
            case 'good':
                $this->messageClass = $alertType;
                $alertType = "success";
                break;
        }
        $this->alertType = $alertType;

        return $this;
    }
}
