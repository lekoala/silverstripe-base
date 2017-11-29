<?php
namespace LeKoala\Base\Dev;

use SilverStripe\Dev\BuildTask;


class FakeRecordGeneratorTask extends BuildTask
{

    protected $title = 'Generate Fake Records';
    protected $description = 'Generate fake records for a given class';
    private static $segment = 'FakeRecordGeneratorTask';

    public function run($request)
    {
        echo 'Please specify one or more of the following options:<br/><br/>';

        $model = $request->getVar('model');
        $how_many = $request->getVar('how_many');
        $member_from_api = $request->getVar('member_from_api');
        if (!$how_many) {
            $how_many = 20;
        }
        if ($member_from_api == '') {
            $member_from_api = true;
        }

        foreach ([
            'model' => 'Which model to generate',
            'how_many' => 'How many records to generate',
            'member_from_api' => 'Use api to generate members',
        ] as $opt => $desc) {
            $v = $ $opt;
            if (empty($v)) {
                $v = '<em>undefined</em>';
            }
            DB::alteration_message($opt . ' - ' . $desc . ' (currently: ' . $v . ')');
        }
        echo '<hr/>';

        if ($model) {
            $sing = singleton($model);

            if ($model == 'Member' && $member_from_api) {
                $data = FakeRecordGenerator::randomUser(['result' => $how_many]);
                foreach ($data as $res) {

                    try {

                        $rec = new Member();
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

                        DB::alteration_message("New record with id $id", "created");
                    } catch (Exception $ex) {
                        DB::alteration_message($ex->getMessage(), "error");
                    }
                }
            } else {
                for ($i = 0; $i < $how_many; $i++) {
                    DB::alteration_message("Generating record $i");

                    try {
                        $rec = $model::create();

                        // Fill according to type
                        $db = $model::config()->db;
                        $has_one = $model::config()->has_one;

                        foreach ($db as $name => $type) {
                            $type = explode('(', $type);
                            switch ($type[0]) {
                                case 'Varchar':
                                    $length = 50;
                                    if (count($type) > 1) {
                                        $length = (int)$type[1];
                                    }
                                    if ($name == 'CountryCode' || $name == 'Nationality') {
                                        $rec->$name = FakeRecordGenerator::countryCode();
                                    } else if ($name == 'PostalCode' || $name == 'Postcode') {
                                        $addr = FakeRecordGenerator::address();
                                        $rec->$name = $addr['Postcode'];
                                    } else if ($name == 'Locality' || $name == 'City') {
                                        $addr = FakeRecordGenerator::address();
                                        $rec->$name = $addr['City'];
                                    } else {
                                        $rec->$name = FakeRecordGenerator::words(3, 7);
                                    }
                                    break;
                                case 'Boolean':
                                    $rec->$name = FakeRecordGenerator::boolean();
                                    break;
                                case 'Enum':
                                    /* @var $enum Enum */
                                    $enum = $rec->dbObject($name);
                                    $rec->$name = FakeRecordGenerator::pick(array_values($enum->enumValues()));
                                    break;
                                case 'Int':
                                    $rec->$name = rand(1, 10);
                                    break;
                                case 'Currency':
                                case 'MyCurrency':
                                    $rec->$name = FakeRecordGenerator::fprand(20, 100, 2);
                                    break;
                                case 'HTMLText':
                                    $rec->$name = FakeRecordGenerator::paragraphs(3, 7);
                                case 'Text':
                                    $rec->$name = FakeRecordGenerator::sentences(3, 7);
                                    break;
                                default:
                                    break;
                            }
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

                        DB::alteration_message("New record with id $id", "created");
                    } catch (Exception $ex) {
                        DB::alteration_message($ex->getMessage(), "error");
                    }
                }
            }
        }
    }
}
