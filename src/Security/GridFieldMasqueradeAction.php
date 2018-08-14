<?php
namespace LeKoala\Base\Security;

use SilverStripe\Control\Session;
use SilverStripe\Forms\GridField\GridField;
use LeKoala\Base\Forms\GridField\GridFieldRowButton;

/**
 * Add a button to masquerade users
 *
 * @author Koala
 */
class GridFieldMasqueradeAction extends GridFieldRowButton
{
    protected $fontIcon = 'torso';

    public function getActionName()
    {
        return 'masquerade';
    }

    public function getButtonLabel()
    {
        return 'Login As';
    }

    /**
     * Handle the actions and apply any changes to the GridField
     *
     * @param GridField $gridField
     * @param string $actionName
     * @param mixed $arguments
     * @param array $data - form data
     * @return void
     */
    public function doHandle(GridField $gridField, $actionName, $arguments, $data)
    {
        /* @var $item Company */
        $item = $gridField->getList()->byID($arguments['RecordID']);
        if (!$item) {
            return;
        }

        $member->masqueradeSession();

        // Save session because we circumvent SS response
        $session = $gridField->getRequest()->getSession();
        $session->save($gridField->getRequest());

        // Go to home page
        header('Location: /');
        exit();
    }
}
