<?php
namespace LeKoala\Base\Forms\FullGridField;

use SilverStripe\ORM\SS_List;
use SilverStripe\Control\Controller;
use LeKoala\Base\Helpers\ClassHelper;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use Symbiote\GridFieldExtensions\GridFieldTitleHeader;
use SilverStripe\Forms\GridField\GridFieldToolbarHeader;
use LeKoala\Base\Forms\FullGridField\FullGridFieldCheckbox;
use LeKoala\Base\Forms\FullGridField\FullGridFieldEditButton;
use LeKoala\Base\Forms\FullGridField\FullGridFieldQuickFilter;

/**
 * The full grid field allows to display a list of data and tick them using checkboxes
 * A bit like an very extended CheckboxSetField
 *
 * @author Koala
 */
class FullGridField extends GridField
{
    private static $allowed_actions = array(
        'index',
        'gridFieldAlterAction',
        'instantSave',
    );

    public function __construct($name, $title = null, SS_List $dataList = null, GridFieldConfig $config = null)
    {
        if (!$config) {
            $config = $this->createDefaultConfig();
        }

        parent::__construct($name, $title, $dataList, $config);
    }

    /**
     * @param HTTPRequest $request
     *
     * @return string
     */
    public function instantSave(HTTPRequest $request)
    {
        $data = $request->requestVars();

        // Protection against CSRF attacks
        $token = $this
            ->getForm()
            ->getSecurityToken();

        // Somehow checkRequest is not working!
        if ($token->getSecurityID() != $request->getHeader('X-CSRF-TOKEN')) {
            $this->httpError(400, _t(
                "SilverStripe\\Forms\\Form.CSRF_FAILED_MESSAGE",
                "There seems to have been a technical problem. Please click the back button, " . "refresh your browser, and try again."
            ));
        }

        $comp = $this->getConfig()->getComponentByType(FullGridFieldCheckbox::class);
        if (!$comp) {
            $this->httpError(400, 'No checkbox component');
        }

        try {
            $message = $comp->handleInstantSave($this, $data);
        } catch (Exception $ex) {
            $message = $ex->getMessage();
        }

        $response = Controller::curr()->getResponse();
        $response->addHeader('X-Status', rawurlencode($message));

        return $response;
    }


    /**
     * Filters to apply to the list if no sql select is provided
     *
     * Cannot be used at the same time as setSqlSelect
     *
     * @param array $filters
     * @return $this
     */
    public function setFilters($filters)
    {
        $this->getConfig()->getComponentByType(FullGridFieldCheckbox::class)->setFilters($filters);
        return $this;
    }

    /**
     * Shorthand for setting relation to target instead of current name
     *
     * @param string $relation
     * @return $this
     */
    public function setSaveToRelation($relation)
    {
        $this->getConfig()->getComponentByType(FullGridFieldCheckbox::class)->setSaveToRelation($relation);
        return $this;
    }

    /**
     * Shorthand for setting sql select
     *
     * This will disable filters (since it's the SQLSelect that will be used)
     *
     * @param SQLSelect $select
     * @return $this
     */
    public function setSqlSelect($select)
    {
        $this->getConfig()->getComponentByType(FullGridFieldCheckbox::class)->setSqlSelect($select);
        return $this;
    }

    /**
     * @return bool
     */
    public function getPreventRemove()
    {
        return $this->getConfig()->getComponentByType(FullGridFieldCheckbox::class)->getPreventRemove();
    }

    /**
     * Prevent removing records even if checkboxes are unticked
     * @return $this
     */
    public function setPreventRemove($preventRemove)
    {
        $this->getConfig()->getComponentByType(FullGridFieldCheckbox::class)->setPreventRemove($preventRemove);
        return $this;
    }

    /**
     * Get the value of cannotBeRemovedIDs
     * @return array
     */
    public function getCannotBeRemovedIDs()
    {
        return $this->getConfig()->getComponentByType(FullGridFieldCheckbox::class)->getCannotBeRemovedIDs();
    }

    /**
     * Set the value of cannotBeRemovedIDs
     *
     * @param array $cannotBeRemovedIDs
     * @return $this
     */
    public function setCannotBeRemovedIDs($cannotBeRemovedIDs)
    {
        $this->getConfig()->getComponentByType(FullGridFieldCheckbox::class)->setCannotBeRemovedIDs($cannotBeRemovedIDs);
        return $this;
    }

    /**
     * Get the value of instantSave
     * @return bool
     */
    public function getInstantSave()
    {
        return   $this->getConfig()->getComponentByType(FullGridFieldCheckbox::class)->getInstantSave();
    }

    /**
     * Set the value of instantSave
     *
     * @param bool $instantSave
     * @return $this
     */
    public function setInstantSave($instantSave)
    {
        $this->getConfig()->getComponentByType(FullGridFieldCheckbox::class)->setInstantSave($instantSave);
        return $this;
    }

    public function createDefaultConfig()
    {
        $config = GridFieldConfig::create();

        $config->addComponent(new GridFieldToolbarHeader());
        $config->addComponent($sort = new GridFieldTitleHeader());
        $config->addComponent($sort = new FullGridFieldQuickFilter());
        //        $config->addComponent($filter     = new GridFieldFilterHeader());
        $config->addComponent(new GridFieldDataColumns());
        //        $config->addComponent(new GridFieldPageCount('toolbar-header-right'));
        //        $config->addComponent($pagination = new GridFieldPaginator(50));
        $config->addComponent(new FullGridFieldCheckbox);
        $config->addComponent(new GridFieldDetailForm);
        $config->addComponent(new FullGridFieldEditButton);

        //        $sort->setThrowExceptionOnBadDataType(false);
        //        $filter->setThrowExceptionOnBadDataType(false);
        //        $pagination->setThrowExceptionOnBadDataType(false);

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes()
    {
        $rec = $this->getForm()->getRecord();
        return array_merge(
            parent::getAttributes(),
            array(
                'data-record' => ClassHelper::sanitiseClassName($rec) . '_' . $rec->ID,
            )
        );
    }
}
