<?php

namespace LeKoala\Base\Forms\GridField;

use SilverStripe\Forms\GridField\GridField_HTMLProvider;

/**
 * A message to display next to a gridfield
 *
 * @author Koala
 */
class GridFieldInfoRow implements GridField_HTMLProvider
{

    /**
     * Fragment to write the button to
     */
    protected $targetFragment;
    protected $content;

    protected $marginBottom = "1rem";
    protected $marginTop = "0";

    /**
     * @param string $targetFragment The HTML fragment to write the button into
     */
    public function __construct($content, $targetFragment = "before")
    {
        $this->content = $content;
        $this->targetFragment = $targetFragment;
    }

    /**
     * Place the export button in a <p> tag below the field
     */
    public function getHTMLFragments($gridField)
    {
        $mt = $this->marginTop;
        $mb = $this->marginBottom;
        return array(
            $this->targetFragment => '<div class="message" style="clear:both;margin-top:' . $mt . ';margin-bottom:' . $mb . ';">' . $this->content . '</div>',
        );
    }

    /**
     * Get the value of marginBottom
     */
    public function getMarginBottom()
    {
        return $this->marginBottom;
    }

    /**
     * Set the value of marginBottom
     *
     * @param string $marginBottom
     */
    public function setMarginBottom($marginBottom)
    {
        $this->marginBottom = $marginBottom;
        return $this;
    }

    /**
     * Get the value of marginTop
     */
    public function getMarginTop()
    {
        return $this->marginTop;
    }

    /**
     * Set the value of marginTop
     *
     * @param string $marginTop
     */
    public function setMarginTop($marginTop)
    {
        $this->marginTop = $marginTop;
        return $this;
    }
}
