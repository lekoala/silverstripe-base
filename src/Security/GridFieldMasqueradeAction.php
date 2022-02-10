<?php

namespace LeKoala\Base\Security;

use SilverStripe\ORM\DataList;
use SilverStripe\Security\Member;
use LeKoala\CmsActions\GridFieldRowButton;
use LeKoala\Base\Security\MasqueradeMember;
use SilverStripe\Forms\GridField\GridField;

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

    public function getButtonLabel(GridField $gridField, $record, $columnName)
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
        /** @var DataList $list  */
        $list = $gridField->getList();
        /** @var Member|MasqueradeMember $item  */
        $item = $list->byID($arguments['RecordID']);
        if (!$item) {
            return;
        }

        $item->masqueradeSession();

        // Save session because we circumvent SS response
        $session = $gridField->getRequest()->getSession();
        $session->save($gridField->getRequest());

        // Go to home page
        header('Location: /');
        exit();
    }
}
