<?php

namespace LeKoala\Base\Dev\Tasks;

use \Exception;
use SilverStripe\Assets\Image;
use LeKoala\Base\Dev\BuildTask;
use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Control\Director;
use LeKoala\Base\Helpers\ClassHelper;
use SilverStripe\Forms\DropdownField;
use SilverStripe\ORM\FieldType\DBInt;
use LeKoala\Base\Dev\FakeDataProvider;
use SilverStripe\ORM\FieldType\DBDate;
use SilverStripe\ORM\FieldType\DBEnum;
use SilverStripe\ORM\FieldType\DBText;
use SilverStripe\ORM\FieldType\DBBoolean;
use SilverStripe\ORM\FieldType\DBVarchar;
use SilverStripe\ORM\FieldType\DBCurrency;
use SilverStripe\ORM\FieldType\DBHTMLText;

class FakeRecordGeneratorTask extends BuildTask
{
    protected $description = 'Generate fake records for a given class';
    private static $segment = 'FakeRecordGeneratorTask';

    public function init()
    {
        $list = $this->getValidDataObjects();
        $this->addOption("model", "Which model to generate", null, $list);
        $this->addOption("how_many", "How many records to generate", 20);
        $this->addOption("member_from_api", "Use https://randomuser.me to generate members", true);
        $this->addOption("clear_existing", "Clear existing data", false);

        $options = $this->askOptions();

        $model = $options['model'];
        $how_many = $options['how_many'];
        $member_from_api = $options['member_from_api'];
        $clear_existing = $options['clear_existing'];

        if ($model) {
            $sing = singleton($model);

            if ($clear_existing) {
                $this->message("Clearing existing data", "warning");
                $cleared = 0;
                foreach ($model::get() as $rec) {
                    $rec->delete();
                    $cleared++;
                }
                $this->message("Cleared $cleared records");
            }

            if ($model == Member::class && $member_from_api) {
                $this->createMembersFromApi($how_many);
            } else {
                for ($i = 0; $i < $how_many; $i++) {
                    $this->message("Generating record $i");

                    try {
                        $rec = $model::create();

                        $fields = $rec->getCMSFields();

                        // Fill according to type
                        $db = $model::config()->db;
                        $owns = $model::config()->owns;
                        $has_one = $model::config()->has_one;
                        $has_many = $model::config()->has_many;
                        $many_many = $model::config()->many_many;

                        foreach ($db as $name => $type) {
                            $rec->$name = $this->getRandomValueFromType($type, $name, $rec);

                            $field = $fields->dataFieldByName($name);
                            if (!$field) {
                                continue;
                            }

                            // For dropdown fields and the likes, we might use the getSource thing
                            if ($field->hasMethod('getSource')) {
                                $source = $field->getSource();
                                if (is_array($source)) {
                                    $source = array_keys($source);
                                    $value = $source[array_rand($source)];

                                    // Use save into to ensure consistency
                                    $field->setValue($value);
                                    $field->saveInto($rec);
                                }
                            }
                        }

                        $hasFillFake = $rec->hasMethod('fillFake');

                        // Only populate relations for record without fillFake
                        if (!$hasFillFake) {
                            foreach ($has_one as $name => $class) {
                                $rel = null;
                                $nameID = $name . 'ID';
                                $isOwned = isset($owns[$name]) ? true : false;

                                if ($isOwned) {
                                    if ($class == Image::class) {
                                        $rel = FakeDataProvider::ownImage($rec, $name);
                                    }
                                } else {
                                    if ($class == Image::class) {
                                        $rel = FakeDataProvider::image();
                                    } else {
                                        $rel = FakeDataProvider::record($class);
                                    }
                                }

                                if ($rel) {
                                    $rec->$nameID = $rel->ID;
                                }
                            }
                            foreach ($many_many as $name => $class) {
                                if (is_array($class)) {
                                    continue;
                                }

                                $rel = null;
                                if ($isOwned) {
                                    if ($class == Image::class) {
                                        $rel = FakeDataProvider::ownImage($rec);
                                    }
                                } else {
                                    $rel = FakeDataProvider::record($class);
                                }
                                if ($rel) {
                                    $rec->$name()->add($rel->ID);
                                }
                            }
                        }

                        $id = $rec->write();

                        if ($hasFillFake) {
                            $rec->fillFake();
                        }

                        $id = $rec->write();

                        $this->message("New record with id $id", "created");
                    } catch (Exception $ex) {
                        $this->message($ex->getMessage(), "error");
                    }
                }
            }
        } else {
            $this->message("Implement 'fillFake' method to create your own fakes");
        }
    }

    protected function getRandomValueFromType($type, $name, $record)
    {
        $type = explode('(', $type);
        switch ($type[0]) {
            case 'Varchar':
            case DBVarchar::class:
                $length = 50;
                if (count($type) > 1) {
                    $length = (int) $type[1];
                }
                if ($name == 'CountryCode' || $name == 'Nationality') {
                    return FakeDataProvider::countryCode();
                } elseif ($name == 'PostalCode' || $name == 'Postcode') {
                    $addr = FakeDataProvider::address();
                    return $addr['Postcode'];
                } elseif ($name == 'Locality' || $name == 'City') {
                    $addr = FakeDataProvider::address();
                    return $addr['City'];
                } elseif ($name == 'URLSegment' || $name == 'Slug') {
                    return null; // let autogeneration happen
                }
                return FakeDataProvider::words(3, 7);
            case 'Date':
            case 'DateTime':
            case DBDate::class:
                return FakeDataProvider::date(strtotime('-1 year'), strtotime('+1 year'));
            case 'Boolean':
            case DBBoolean::class:
                return FakeDataProvider::boolean();
            case 'Enum':
            case 'NiceEnum':
            case DBEnum::class:
                /* @var $enum Enum */
                $enum = $record->dbObject($name);
                return FakeDataProvider::pick(array_values($enum->enumValues()));
            case 'Int':
            case DBInt::class:
                return rand(1, 10);
            case 'Currency':
            case DBCurrency::class:
                return FakeDataProvider::fprand(20, 100, 2);
            case 'HTMLText':
            case DBHTMLText::class:
                return FakeDataProvider::paragraphs(3, 7);
            case 'Text':
            case DBText::class:
                return FakeDataProvider::sentences(3, 7);
            default:
                $dbObject = $record->dbObject($name);
                if ($dbObject && $dbObject->hasMethod('fillFake')) {
                    return $dbObject->fillFake();
                }
                return null;
        }
    }

    protected function createMembersFromApi($how_many)
    {
        $data = FakeDataProvider::randomUser(['result' => $how_many]);
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
                $image = FakeDataProvider::storeFakeImage($image_data, basename($res['picture']['large']), 'Avatars');
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

    public function isEnabled()
    {
        return Director::isDev();
    }
}
