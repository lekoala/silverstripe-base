<?php
namespace LeKoala\Base\Forms;

use Exception;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Validator;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Forms\RequiredFields;

/**
 * An extended class for forms
 */
class BaseForm extends Form
{
    /**
     * @var array
     */
    protected $params;
    /**
     * @var DataObject
     */
    protected $record;

    /**
     * @param RequestHandler $controller
     * @param mixed $name Extended to allow passing
     * @param FieldList $fields
     * @param FieldList $actions
     * @param Validator $validator
     */
    public function __construct(
        RequestHandler $controller = null,
        $name = self::DEFAULT_NAME,
        FieldList $fields = null,
        FieldList $actions = null,
        Validator $validator = null
    ) {
        // We hack the name argument to pass parameters
        // Either an array or a DataObject
        // This allows us to inject parameters and not call the controller from the form
        if (!is_string($name)) {
            if (is_array($name)) {
                $this->params = $name;
            } elseif (is_object($name)) {
                $this->record = $name;
            } else {
                throw new Exception("name must be a string, an array or a DataObject");
            }
            $name = self::DEFAULT_NAME;
        }
        $fields = $this->buildFields(BuildableFieldList::fromFieldList($fields));
        if (!$fields) {
            throw new Exception("buildFields must return the FieldList instance");
        }
        $actions = $this->buildActions(BuildableFieldList::fromFieldList($actions));
        if (!$actions) {
            throw new Exception("buildActions must return the FieldList instance");
        }
        if ($validator === null) {
            $validator = $this->buildValidator($fields);
            if (!$validator) {
                throw new Exception("buildValidator must return a validator");
            }
        }
        parent::__construct($controller, $name, $fields, $actions, $validator);
    }

    protected function buildFields(BuildableFieldList $fields)
    {
        return $fields;
    }

    protected function buildActions(BuildableFieldList $actions)
    {
        return $actions;
    }
    protected function buildValidator(BuildableFieldList $fields)
    {
        return new RequiredFields;
    }

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
