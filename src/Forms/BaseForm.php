<?php
namespace LeKoala\Base\Forms;

use Exception;
use SilverStripe\Forms\Form;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Validator;
use SilverStripe\View\Requirements;
use LeKoala\Base\Helpers\ClassHelper;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Control\RequestHandler;

/**
 * An extended class for forms:
 *
 * - Each for has a class base on its class name (or Type method)
 * - The second argument "name" can be use to bind params or a dataobject to the form
 * - buildFields, buildActions and buildValidator methods allow to define easily your fields
 * - getController is docblocked to return a proper instance of BaseContentController
 * - getLogger give you access to the app logger with a channel
 * - the requirements method is called from the constructor to add optionnal assets
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
     * @var string
     */
    protected $recordType;
    /**
     * @var boolean
     */
    protected $jsValidationEnabled = false;

    /**
     * @param RequestHandler $controller
     * @param mixed $name Extended to allow passing objects directly
     * @param FieldList $fields
     * @param FieldList $actions
     * @param Validator $validator
     */
    public function __construct(
        RequestHandler $controller = null,
        $name = null,
        FieldList $fields = null,
        FieldList $actions = null,
        Validator $validator = null
    ) {
        $this->addExtraClass($this->Type());
        if ($this->jsValidationEnabled) {
            $this->enableJsValidation();
        }
        // We hack the name argument to pass parameters
        // Either an array or a DataObject
        // This allows us to inject parameters and not call the controller from the form
        if ($name && !is_string($name)) {
            if (is_array($name)) {
                $this->params = $name;
            } elseif (is_object($name)) {
                $recordType = $this->recordType;
                if (!$recordType) {
                    $recordType = DataObject::class;
                }
                if (!$name instanceof $recordType) {
                    throw new Exception("Object must be an instance of $recordType, it is: " . get_class($name));
                }
                $this->record = $name;
            } else {
                throw new Exception("name must be a string, an array or a DataObject");
            }
            $name = null;
        }
        // name should be the same as the class by default
        // the name is used to determine which function is called on your controller
        // therefore, it's easier to declare a function that matches the class name
        if (!$name) {
            $name = $this->Type();
        }
        $fields = $this->buildFields(BuildableFieldList::fromFieldList($fields));
        if (!$fields) {
            throw new Exception("buildFields must return the FieldList instance in " . static::class);
        }
        // Attach record as hidden fields, these can be used by the controller
        // To properly restore the record on POST if it was depending on url params or query string
        if ($this->record) {
            $fields->addHidden('_RecordID', ['value' => $this->record->ID]);
            $fields->addHidden('_RecordClassName', ['value' => $this->record->ClassName]);
        }
        $actions = $this->buildActions(BuildableFieldList::fromFieldList($actions));
        if (!$actions) {
            throw new Exception("buildActions must return the FieldList instance in " . static::class);
        }
        if ($validator === null) {
            $validator = $this->buildValidator($fields);
            if (!$validator) {
                throw new Exception("buildValidator must return a validator in " . static::class);
            }
        }
        parent::__construct($controller, $name, $fields, $actions, $validator);
        $this->requirements();
        if ($this->record) {
            $this->loadDataFrom($this->record);
        } elseif ($this->params) {
            $this->loadDataFrom($this->params);
        }
    }

    protected function Type()
    {
        return ClassHelper::getClassWithoutNamespace(get_called_class());
    }

    protected function requirements()
    {
        // Add your requirements calls here
    }

    /**
     * @param BuildableFieldList $fields
     * @return BuildableFieldList
     */
    protected function buildFields(BuildableFieldList $fields)
    {
        return $fields;
    }

    /**
     * @param BuildableFieldList $fields
     * @return BuildableFieldList
     */
    protected function buildActions(BuildableFieldList $actions)
    {
        // If we have a doSubmit method, add the action automatically
        if (method_exists($this, 'doSubmit')) {
            $actions->addAction("doSubmit", _t('BaseForm.DOSUBMIT', "Submit"));
        }
        return $actions;
    }

    /**
     * @param BuildableFieldList $fields
     * @return Validator
     */
    protected function buildValidator(BuildableFieldList $fields)
    {
        return new RequiredFields;
    }

    /**
     * Manually enable RequiredFields javascript validation
     *
     * You can also use JsRequiredFields class
     *
     * @return void
     */
    protected function enableJsValidation()
    {
        $this->setAttribute("data-module", "RequiredFields");
        Requirements::javascript("base/javascript/ModularBehaviour.js");
        Requirements::javascript("base/javascript/RequiredFields.js");
    }

    /**
     * @return \LeKoala\Base\Controllers\BaseContentController
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

    /**
     * @param string $message
     * @return HTTPResponse
     */
    public function success($message)
    {
        $this->sessionMessage($message, "good");
        return $this->getController()->redirectBack();
    }

    /**
     * @param string $message
     * @return HTTPResponse
     */
    public function error($message = null)
    {
        if ($message === null) {
            $message = _t('Global.UNDEFINED_ERROR', "Something wrong happened");
        }
        $this->sessionError($message, "bad");
        return $this->getController()->redirectBack();
    }
}
