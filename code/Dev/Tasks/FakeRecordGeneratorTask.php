<?php
namespace LeKoala\Base\Dev\Tasks;

use \Exception;
use LeKoala\Base\Dev\BuildTask;
use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\ORM\FieldType\DBInt;
use SilverStripe\ORM\FieldType\DBDate;
use SilverStripe\ORM\FieldType\DBEnum;
use SilverStripe\ORM\FieldType\DBText;
use SilverStripe\ORM\FieldType\DBBoolean;
use SilverStripe\ORM\FieldType\DBVarchar;
use SilverStripe\ORM\FieldType\DBCurrency;
use SilverStripe\ORM\FieldType\DBHTMLText;

class FakeRecordGeneratorTask extends BuildTask
{

    protected $title = 'Generate Fake Records';
    protected $description = 'Generate fake records for a given class';
    private static $segment = 'FakeRecordGeneratorTask';

    public function run($request)
    {
        parent::run($request);

        $list = $this->getValidDataObjects();
        $this->addOption("model", "Which model to generate", null, $list);
        $this->addOption("how_many", "How many records to generate", 20);
        $this->addOption("member_from_api", "Use api to generate members", true);

        $options = $this->askOptions();

        $model = $options['model'];
        $how_many = $options['how_many'];
        $member_from_api = $options['member_from_api'];

        if ($model) {
            $sing = singleton($model);

            if ($model == Member::class && $member_from_api) {
                $this->createMembersFromApi($how_many);
            } else {
                for ($i = 0; $i < $how_many; $i++) {
                    $this->message("Generating record $i");

                    try {
                        $rec = $model::create();

                        // Fill according to type
                        $db = $model::config()->db;
                        $has_one = $model::config()->has_one;

                        foreach ($db as $name => $type) {
                            $rec->$name = $this->getRandomValueFromType($type, $name);
                        }

                        foreach ($has_one as $name => $class) {
                            $nameID = $name . 'ID';
                            if ($class == 'Image') {
                                $rel = FakeRecordGenerator::image();
                            } else {
                                $rel = FakeRecordGenerator::record($class);
                            }
                            if ($rel) {
                                $rec->$nameID = $rel->ID;
                            }
                        }

                        $id = $rec->write();

                        if ($rec->hasMethod('fillFake')) {
                            $rec->fillFake();
                        }
                        $id = $rec->write();

                        $this->message("New record with id $id", "created");
                    } catch (Exception $ex) {
                        $this->message($ex->getMessage(), "error");
                    }
                }
            }
        }
    }

    protected function getRandomValueFromType($type, $name)
    {
        $type = explode('(', $type);
        switch ($type[0]) {
            case 'Varchar':
            case DBVarchar::class:
                $length = 50;
                if (count($type) > 1) {
                    $length = (int)$type[1];
                }
                if ($name == 'CountryCode' || $name == 'Nationality') {
                    return FakeRecordGenerator::countryCode();
                } else if ($name == 'PostalCode' || $name == 'Postcode') {
                    $addr = FakeRecordGenerator::address();
                    return $addr['Postcode'];
                } else if ($name == 'Locality' || $name == 'City') {
                    $addr = FakeRecordGenerator::address();
                    return $addr['City'];
                }
                return FakeRecordGenerator::words(3, 7);
            case 'Date':
            case 'DateTime':
            case DBDate::class:
                return FakeRecordGenerator::date(strtotime('-1 year'), strtotime('+1 year'));
            case 'Boolean':
            case DBBoolean::class:
                return FakeRecordGenerator::boolean();
            case 'Enum':
            case DBEnum::class:
                /* @var $enum Enum */
                $enum = $rec->dbObject($name);
                return FakeRecordGenerator::pick(array_values($enum->enumValues()));
            case 'Int':
            case DBInt::class:
                return rand(1, 10);
            case 'Currency':
            case DBCurrency::class:
                return FakeRecordGenerator::fprand(20, 100, 2);
            case 'HTMLText':
            case DBHTMLText::class:
                return FakeRecordGenerator::paragraphs(3, 7);
            case 'Text':
            case DBText::class:
                return FakeRecordGenerator::sentences(3, 7);
            default:
                return null;
        }
    }

    protected function createMembersFromApi($how_many)
    {
        $data = FakeRecordGenerator::randomUser(['result' => $how_many]);
        foreach ($data as $res) {
            try {
                $rec = Member::create();
                $rec->Gender = $res['gender'];
                $rec->FirstName = ucwords($res['name']['first']);
                $rec->Surname = ucwords($res['name']['last']);
                $rec->Salutation = ucwords($res['name']['title']);
                $rec->Address = $res['location']['street'];
                $rec->Locality = $res['location']['city'];
                $rec->PostalCode = $res['location']['postcode'];
                $rec->BirthDate = $res['dob'];
                $rec->Created = $res['registered'];
                $rec->Phone = $res['phone'];
                $rec->Cell = $res['cell'];
                $rec->Nationality = $res['nat'];
                $rec->Email = $res['email'];

                $image_data = file_get_contents($res['picture']['large']);
                $image = FakeRecordGenerator::storeFakeImage($image_data, basename($res['picture']['large']), 'Avatars');
                $rec->AvatarID = $image->ID;

                $id = $rec->write();

                $rec->changePassword($res['login']['password']);

                if ($rec->hasMethod('fillFake')) {
                    $rec->fillFake();
                }
                $id = $rec->write();

                $this->message("New record with id $id", "created");
            } catch (Exception $ex) {
                $this->message($ex->getMessage(), "error");
            }
        }
    }
}
