<?php
namespace LeKoala\Base\Actions;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FieldList;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest;
/**
 * Decorates {@link GridDetailForm_ItemRequest} to use new form actions and buttons.
 *
 * This is a lightweight version of BetterButtons that use default getCMSActions functionnality
 * on DataObjects
 *
 * @link https://github.com/unclecheese/silverstripe-gridfield-betterbuttons
 * @property \SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest|\LeKoala\Base\Actions\ActionsGridFieldItemRequest $owner
 */
class ActionsGridFieldItemRequest extends DataExtension
{
    /**
     * @var array Allowed controller actions
     */
    private static $allowed_actions = array(
        'doSaveAndClose',
        'doCustomAction',
    );
    /**
     * Updates the detail form to include new form actions and buttons
     *
     * @param Form The ItemEditForm object
     */
    public function updateItemEditForm($form)
    {
        $CMSActions = $this->owner->record->getCMSActions();
        /* @var $actions FieldList */
        $actions = $form->Actions();
        foreach ($CMSActions as $action) {
            $actions->push($action);
        }
        // Move delete at the end
        $deleteAction = $actions->fieldByName('action_doDelete');
        if ($deleteAction) {
            $actions->remove($deleteAction);
            $actions->push($deleteAction);
            // Really you don't want to click on it by mistake!
            $deleteAction->setAttribute('style', 'position:absolute;right:1em;');
        }
        // Move cancel at the end
        $cancelButton = $actions->fieldByName('cancelbutton');
        if ($cancelButton) {
            $actions->remove($cancelButton);
            $actions->push($cancelButton);
        }
    }
    /**
     * Handles custom actions
     *
     * @param array The form data
     * @param Form The form object
     */
    public function doCustomAction($data, $form)
    {
        $action = key($data['action_doCustomAction']);
        $controller = $this->getToplevelController();
        $record = $this->owner->record;
        // Check permission
        if (!$this->owner->record->canEdit()) {
            return $this->httpError(403);
        }
        // TODO: add security check
        $message = null;
        $error = false;
        try {
            $result = $record->$action($data, $form, $controller);
            if ($result === false) {
                $error = true;
            } else if (is_string($result)) {
                $message = $result;
            }
        } catch (\Exception $ex) {
            $error = true;
            $message = $ex->getMessage();
        }
        $isNewRecord = $record->ID == 0;
        // Build default message
        if (!$message) {
            $message = _t(
                'ActionsGridFieldItemRequest',
                'Action {action} was done on {name}',
                array(
                    'action' => $action,
                    'name' => $record->i18n_singular_name(),
                )
            );
        }
        $status = 'good';
        if ($error) {
            $status = 'bad';
        }
        $form->sessionMessage($message, $status, ValidationResult::CAST_HTML);
        // Redirect after save
        return $this->redirectAfterSave($isNewRecord);
    }
    /**
     * Saves the form and goes back to list view
     *
     * @param array The form data
     * @param Form The form object
     */
    public function doSaveAndClose($data, $form)
    {
        $result = $this->owner->doSave($data, $form);
        // Redirect after save
        $controller = $this->getToplevelController();
        $controller->getResponse()->addHeader("X-Pjax", "Content");
        return $controller->redirect($this->getBackLink());
    }
    /**
     * Gets the top level controller.
     *
     * @return Controller
     * @todo  This had to be directly copied from {@link GridFieldDetailForm_ItemRequest}
     * because it is a protected method and not visible to a decorator!
     */
    protected function getToplevelController()
    {
        $c = $this->owner->getController();
        while ($c && $c instanceof GridFieldDetailForm_ItemRequest) {
            $c = $c->getController();
        }
        return $c;
    }
    /**
     * Gets the back link
     *
     * @return  string
     * @todo  This had to be directly copied from {@link GridFieldDetailForm_ItemRequest}
     * because it is a protected method and not visible to a decorator!
     */
    public function getBackLink()
    {
        // TODO Coupling with CMS
        $backlink = '';
        $toplevelController = $this->getToplevelController();
        if ($toplevelController && $toplevelController instanceof LeftAndMain) {
            if ($toplevelController->hasMethod('Backlink')) {
                $backlink = $toplevelController->Backlink();
            } elseif ($this->owner->getController()->hasMethod('Breadcrumbs')) {
                $parents = $this->owner->getController()->Breadcrumbs(false)->items;
                $backlink = array_pop($parents)->Link;
            }
        }
        if (!$backlink) {
            $backlink = $toplevelController->Link();
        }
        return $backlink;
    }
    /**
     * Response object for this request after a successful save
     *
     * @param bool $isNewRecord True if this record was just created
     * @return HTTPResponse|DBHTMLText
     * @todo  This had to be directly copied from {@link GridFieldDetailForm_ItemRequest}
     * because it is a protected method and not visible to a decorator!
     */
    protected function redirectAfterSave($isNewRecord)
    {
        $controller = $this->getToplevelController();
        if ($isNewRecord) {
            return $controller->redirect($this->Link());
        } elseif ($this->owner->gridField->getList()->byID($this->owner->record->ID)) {
            // Return new view, as we can't do a "virtual redirect" via the CMS Ajax
            // to the same URL (it assumes that its content is already current, and doesn't reload)
            return $this->owner->edit($controller->getRequest());
        } else {
            // Changes to the record properties might've excluded the record from
            // a filtered list, so return back to the main view if it can't be found
            $url = $controller->getRequest()->getURL();
            $noActionURL = $controller->removeAction($url);
            $controller->getRequest()->addHeader('X-Pjax', 'Content');
            return $controller->redirect($noActionURL, 302);
        }
    }
}