<?php

namespace LeKoala\Base\Admin;

use SilverStripe\Forms\Form;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\ArrayData;
use SilverStripe\Admin\ModelAdmin;
use LeKoala\Base\Helpers\ClassHelper;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use LeKoala\Base\Subsite\SubsiteHelper;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Admin\AdminRootController;
use SilverStripe\Forms\GridField\GridField;
use LeKoala\CommonExtensions\SortableExtension;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Control\PjaxResponseNegotiator;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

/**
 * Improved ModelAdmin
 * - Built in subsite support
 * - Helpers
 *
 */
abstract class BaseModelAdmin extends ModelAdmin
{
    /**
     * Do not delete from list by default
     *
     * @config
     * @var boolean
     */
    private static $can_delete_from_list = false;

    private static $allowed_actions = array(
        'customResponse',
        'ShowSpec',
        'ImportForm',
        'SearchForm'
    );

    protected function init()
    {
        parent::init();

        // Optimize search form
        if (strpos($this->getRequest()->getURL(), 'schema/SearchForm') !== false) {
            session_write_close();
        }
    }

    public function ShowSpec()
    {
        $modelSNG = singleton($this->owner->modelClass);
        $modelName = $modelSNG->i18n_singular_name();
        $importers = $this->getModelImporters();
        // get HTML specification for each import (column names etc.)
        $importerClass = $importers[$this->modelTab];
        /** @var BulkLoader $importer */
        $importer = new $importerClass($this->modelClass);
        $spec = $importer->getImportSpec();
        $specFields = new ArrayList();
        foreach ($spec['fields'] as $name => $desc) {
            $specFields->push(new ArrayData(array('Name' => $name, 'Description' => $desc)));
        }
        $specRelations = new ArrayList();
        foreach ($spec['relations'] as $name => $desc) {
            $specRelations->push(new ArrayData(array('Name' => $name, 'Description' => $desc)));
        }
        $specHTML = $this->customise(array(
            'ClassName' => $this->sanitiseClassName($this->modelClass),
            'ModelName' => Convert::raw2att($modelName),
            'Fields' => $specFields,
            'Relations' => $specRelations,
        ))->renderWith(['EmptyPage', $this->getTemplatesWithSuffix('_ShowSpec')]);
        return $specHTML;
    }

    public function getManagedModels()
    {
        $models = parent::getManagedModels();

        // Allow removing models through config
        $unmodels = $this->config()->get('unmanaged_models');
        if ($unmodels) {
            if (is_string($unmodels)) {
                $unmodels = array($unmodels);
            }
            foreach ($unmodels as $k => $v) {
                unset($models[$v]);
            }
        }

        return $models;
    }


    /**
     * @return int
     */
    public function getSubsiteId()
    {
        return SubsiteHelper::currentSubsiteID();
    }

    /**
     * Allow rendering content with custom template
     *
     * Will look in templates/Includes/YourModelAdminClass_{fragment}.ss
     *
     * Typical usage is this : return $this->getCustomResponseNegotiator(__FUNCTION__)->respond($this->getRequest());
     *
     * @param string $fragment
     * @return PjaxResponseNegotiator
     */
    public function getCustomResponseNegotiator($fragment)
    {
        return new PjaxResponseNegotiator(
            array(
                'CurrentForm' => function () {
                    return $this->getEditForm()->forTemplate();
                },
                'Content' => function () use ($fragment) {
                    $fragmentTemplate = $this->renderWith(
                        '\LeKoala\Base\BaseModelAdminFragment',
                        [
                            'Content' =>  $this->renderWith($this->getTemplatesWithSuffix('_' . $fragment)),
                            'GoBackLink' => self::getBaseLink($this->modelClass),
                        ]
                    );
                    return $fragmentTemplate;
                },
                'Breadcrumbs' => function () {
                    return $this->renderWith([
                        'type' => 'Includes',
                        'SilverStripe\\Admin\\CMSBreadcrumbs'
                    ]);
                },
                'default' => function () use ($fragment) {
                    $fragmentTemplate = $this->renderWith(
                        '\LeKoala\Base\BaseModelAdminFragment',
                        [
                            'Content' =>  $this->renderWith($this->getTemplatesWithSuffix('_' . $fragment)),
                            'GoBackLink' => self::getBaseLink($this->modelClass),
                        ]
                    );
                    return $this->renderWith(
                        $this->getViewer('fragment'),
                        [
                            'Content' => $fragmentTemplate
                        ]
                    );
                }
            ),
            $this->getResponse()
        );
    }

    public static function getRequiredPermissions()
    {
        // This is needed to avoid BaseModelAdmin to be displayed as a valid permission
        if (get_called_class() == self::class) {
            return false;
        }
        return parent::getRequiredPermissions();
    }

    /**
     * Get the record asked by CustomLink or CMSInlineAction
     *
     * @return DataObject|null
     */
    public function getRequestedRecord()
    {
        $request = $this->getRequest();

        // Look first in headers
        $class = $request->getHeader('X-RecordClassName');
        if (!$class) {
            $class = $request->requestVar('_RecordClassName');
        }
        $ID = $request->param('ID');
        // ModelClass can be forwarded in a get var from CustomLink
        if (!$class) {
            $class = $request->getVar('ModelClass');
            if (!$ID) {
                $ID = $request->getVar('ID');
            }
        }
        if (!$class) {
            // Help our fellow developpers
            if ($ID == 'field') {
                throw new ValidationException("Attempt to post on a FormField often result in loosing request params. No record class could be found");
            }
            throw new ValidationException("No class in request");
        }
        if (!ClassHelper::isValidDataObject($class)) {
            throw new ValidationException("$class is not valid");
        }
        if (!$ID || $ID == 'field') {
            $ID = $request->getHeader('X-RecordID');
        }
        if (!$ID || $ID == 'field') {
            $ID = (int) $request->requestVar('_RecordID');
        }
        return DataObject::get_by_id($class, $ID);
    }

    public function handleRequest(HTTPRequest $request): HTTPResponse
    {
        $SubsiteID = $request->getVar('SubsiteID');
        if ($SubsiteID) {
            // We are editing something, move to root
            if (strpos($request->getURL(), '/EditForm/') !== false) {
                header('Location: /' . ltrim($this->Link(), '/'));
                exit();
            }
        }

        $response = parent::handleRequest($request);
        // Force reload since sometimes pjax does not refresh properly everything :-(
        // ! Don't do this, it breaks save and close functionnality
        // if ($response->getHeader('X-Reload') === null) {
        //     $response->addHeader('X-Reload', true);
        // }
        return $response;
    }

    public function getList()
    {
        $list = parent::getList();
        $singl = singleton($this->modelClass);
        $config = $singl->config();

        // Sort by custom sort order
        if ($config->model_admin_sort) {
            $list = $list->sort($config->model_admin_sort);
        }

        return $list;
    }

    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);

        $singl = singleton($this->modelClass);

        $gridField = $this->getGridFieldFrom($form);
        $gridField->getConfig()->removeComponentsByType(GridFieldDeleteAction::class);
        if (self::config()->can_delete_from_list) {
            $gridField->getConfig()->addComponent(new GridFieldDeleteAction(false));
        }
        if ($singl->hasExtension(SortableExtension::class)) {
            $gridField->getConfig()->addComponent(new GridFieldOrderableRows());
        }

        return $form;
    }

    /**
     * @return string
     */
    protected function getSanitisedModelClass()
    {
        return $this->sanitiseClassName($this->modelClass);
    }

    /**
     * Get gridfield for current model
     * Makes it easy for your ide
     *
     * In SS 4.6 there is a getGridField method that create the gridfield..
     * this method fetches the actual gridfield from the fields
     *
     * @param Form $form
     * @return GridField
     */
    public function getGridFieldFrom(Form $form)
    {
        return $form->Fields()->dataFieldByName($this->getSanitisedModelClass());
    }

    /**
     * Render a dialog
     *
     * @param array $customFields
     * @return string
     */
    protected function renderDialog($customFields = null)
    {
        // Set empty content by default otherwise it will render the full page
        if (empty($customFields['Content'])) {
            $customFields['Content'] = '';
        }
        return $this->renderWith('SilverStripe\\Admin\\CMSDialog', $customFields);
    }

    /**
     * @param DataObject|ArrayData $record
     * @return string
     */
    public static function getEditLink($record)
    {
        $URLSegment = static::config()->url_segment;
        $recordClass = $record->ClassName;
        $sanitisedClass = ClassHelper::sanitiseClassName($recordClass);
        $ID = $record->ID;
        $AdminURL = trim(AdminRootController::admin_url(), '/');
        return "/$AdminURL/$URLSegment/$sanitisedClass/EditForm/field/$sanitisedClass/item/$ID/edit";
    }

    /**
     * @param string $class
     * @return string
     */
    public static function getBaseLink($class, $link = null)
    {
        $URLSegment = static::config()->url_segment;
        $sanitisedClass = ClassHelper::sanitiseClassName($class);
        $AdminURL = trim(AdminRootController::admin_url(), '/');
        return self::join_links("/$AdminURL/$URLSegment/$sanitisedClass", $link);
    }
}
