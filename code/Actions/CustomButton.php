<?php
namespace LeKoala\Base\Actions;

trait CustomButton
{
    /**
     * Default classes applied in constructor
     * @config
     * @var array
     */
    private static $default_classes = [
        'btn', 'btn-info'
    ];

    /**
     * An icon for this button
     * @var string
     */
    protected $buttonIcon;

    public function setButtonType($type)
    {
        if ($this->extraClasses) {
            foreach ($this->extraClasses as $k => $v) {
                if (strpos($k, 'btn-') !== false) {
                    unset($this->extraClasses[$k]);
                }
            }
        }

        $btn = "btn-$type";
        $this->extraClasses[$btn] = $btn;
    }

    /**
     * Get the title with icon if set
     *
     * @return string
     */
    protected function getButtonTitle()
    {
        $title = $this->title;
        if ($this->buttonIcon) {
            $title = '<span class="font-icon-' . $this->buttonIcon . '"></span> ' . $title;
        }
        return $title;
    }

    /**
     * Get an icon for this button
     *
     * @return string
     */
    public function getButtonIcon()
    {
        return $this->buttonIcon;
    }

    /**
     * Set an icon for this button
     *
     * Feel free to use SilverStripeIcons constants
     *
     * @param string $buttonIcon An icon for this button
     * @return self
     */
    public function setButtonIcon(string $buttonIcon)
    {
        $this->buttonIcon = $buttonIcon;
        return $this;
    }
}
