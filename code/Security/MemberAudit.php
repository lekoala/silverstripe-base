<?php
namespace LeKoala\Base\Security;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use LeKoala\Base\ORM\FieldType\DBJson;

/**
 * Audit specific events that may require more attention
 *
 * Simply call $member->audit('myevent',$mydata) to create a new audit record
 *
 * @property string $Event
 * @property string $AuditData
 * @property string $IP
 * @property int $MemberID
 * @method \SilverStripe\Security\Member Member()
 * @mixin \LeKoala\Base\Extensions\IPExtension
 */
class MemberAudit extends DataObject
{
    private static $table_name = 'MemberAudit'; // When using namespace, specify table name

    public static $rename_columns = [
        'Ip' => 'IP',
        'Action' => 'Event',
        'Data' => 'AuditData'
    ];
    private static $db = [
        'Event' => 'Varchar(39)',
        'AuditData' => DBJson::class,
    ];
    private static $has_one = [
        'Member' => Member::class,
    ];
    private static $summary_fields = array(
        'Created', 'Action', 'AuditData'
    );
    private static $default_sort = 'Created DESC';

    public function forTemplate()
    {
        return $this->Created . ' - ' . $this->Action;
    }

    public function canEdit($member = null)
    {
        return false;
    }

    public function canDelete($member = null)
    {
        return false;
    }
}
