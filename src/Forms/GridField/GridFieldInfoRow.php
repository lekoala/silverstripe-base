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
        return array(
            $this->targetFragment => '<div class="message" style="margin-bottom:10px;">' . $this->content . '</div>',
        );
    }
}
