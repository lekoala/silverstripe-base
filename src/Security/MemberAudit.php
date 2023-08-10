<?php

namespace LeKoala\Base\Security;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use LeKoala\Base\ORM\FieldType\DBJson;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Security;

/**
 * Audit specific events that may require more attention
 *
 * Simply call $member->audit('myevent',$mydata) to create a new audit record
 *
 * Try to keep "myevent" simple and consistent and set variable data in the data parameter
 *
 * @property string $IP
 * @property string $Event
 * @property string $AuditData
 * @property int $MemberID
 * @property int $SourceMemberID
 * @method \SilverStripe\Security\Member Member()
 * @method \SilverStripe\Security\Member SourceMember()
 */
class MemberAudit extends DataObject
{
    private static $table_name = 'MemberAudit'; // When using namespace, specify table name

    public static $rename_columns = [
        'Ip' => 'IP',
        'Action' => 'Event', // Action is reserved
        'Data' => 'AuditData'
    ];
    private static $db = [
        'Event' => 'Varchar(39)',
        'AuditData' => DBJson::class,
    ];
    private static $has_one = [
        'Member' => Member::class,
        'SourceMember' => Member::class,
    ];
    private static $summary_fields = array(
        'Created', 'Event', 'SourceMember.Title', 'AuditData.LimitCharacters'
    );
    private static $default_sort = 'Created DESC';

    /**
     * @config
     * @var string
     */
    private static $keep_duration = "-1 year";

    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();

        $member = Security::getCurrentUser();
        $this->SourceMemberID = $member ? $member->ID : 0;
    }

    public static function clearOldRecords()
    {
        $keep_duration = self::config()->keep_duration;
        if (!$keep_duration) {
            return;
        }
        $date = date('Y-m-d', strtotime($keep_duration));
        DB::query("DELETE FROM MemberAudit WHERE Created < '$date'");
    }

    public function getCMSFields()
    {
        $fields =  parent::getCMSFields();

        $fields->replaceField("AuditData", new TextField("AuditData"));

        return $fields;
    }

    public function forTemplate()
    {
        return $this->Created . ' - ' . $this->Event;
    }

    public function canEdit($member = null)
    {
        return false;
    }

    public function canDelete($member = null)
    {
        return false;
    }

    public function AuditDataShort()
    {
        return substr((string)$this->AuditData, 0, 100) . '...';
    }
}
