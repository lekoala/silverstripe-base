<?php
namespace LeKoala\Base\Forms;

use SilverStripe\Forms\Form;

/**
 * An extended class for forms
 */
class BaseForm extends Form
{
    /**
     * @return \LeKoala\Base\ContentController
     */
    public function getController()
    {
        return parent::getController();
    }

    /**
     * @return  Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->getController()->getLogger()->withName($this->getName());
    }
}
