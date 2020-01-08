<?php

namespace LeKoala\Base\Dev;

use Exception;
use SilverStripe\Assets\Image;
use SilverStripe\Assets\Folder;
use SilverStripe\ORM\DataObject;
use SilverStripe\Control\Director;
use LeKoala\Base\Geo\CountriesList;
use SilverStripe\Assets\FileNameFilter;

/**
 * Provide fake data for random record generation
 */
class FakeDataProvider
{
    protected static $folder = 'Faker';
    protected static $latitude = 50.7802;
    protected static $longitude = 4.4269;
    protected static $avatarsPath = 'resources/avatars';
    protected static $firstNames = [
        'Caecilius', 'Quintus', 'Horatius', 'Flaccus', 'Clodius',
        'Metellus', 'Flavius', 'Hortensius', 'Julius', 'Decimus', 'Gaius'
    ];
    protected static $lastNames = [
        'Gracchus', 'Antonius', 'Brutus', 'Cassius', 'Casca', 'Lepidus',
        'Crassus', 'Cinna'
    ];
    protected static $addresses = [
        [
            'Address' => '4880 Glory Rd',
            'City' => 'Ponchatoula',
            'Postcode' => 'LA 70454',
            'Country' => 'US'
        ],
        [
            'Address' => '4363 Willow Oaks Lane',
            'City' => 'Harrison Township',
            'Postcode' => 'NJ 08062',
            'Country' => 'US'
        ],
        [
            'Address' => '3471 Chipmunk Ln',
            'City' => 'Clifton Heights',
            'Postcode' => 'PA 19018 ‎',
            'Country' => 'US'
        ],
        [
            'Address' => '666 Koala Ln',
            'City' => 'Mt Laurel',
            'Postcode' => 'NJ 08054‎',
            'Country' => 'US'
        ],
        [
            'Address' => '3339 Little Acres Ln',
            'City' => 'Woodford',
            'Postcode' => 'VA 22580',
            'Country' => 'US'
        ],
        [
            'Address' => '15 Anthony Avenue',
            'City' => 'Essex',
            'Postcode' => 'MD 21221',
            'Country' => 'US'
        ],
        [
            'Address' => '2942 Kelly Ave',
            'City' => 'Baltimore',
            'Postcode' => 'MD 21209',
            'Country' => 'US'
        ],
        [
            'Address' => '687 Burke Rd',
            'City' => 'Delta',
            'Postcode' => 'PA 17314',
            'Country' => 'US'
        ],
        [
            'Address' => '1196 Court St',
            'City' => 'York',
            'Postcode' => 'PA 17404 ‎',
            'Country' => 'US'
        ],
        [
            'Address' => '25 Barnes St',
            'City' => 'Bel Air',
            'Postcode' => 'MD 21014',
            'Country' => 'US'
        ],
    ];
    protected static $domains = ['perdu.com', 'silverstripe.org', 'google.be'];
    protected static $words = [
        'lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing',
        'elit', 'curabitur', 'vel', 'hendrerit', 'libero', 'eleifend',
        'blandit', 'nunc', 'ornare', 'odio', 'ut', 'orci',
        'gravida', 'imperdiet', 'nullam', 'purus', 'lacinia', 'a',
        'pretium', 'quis', 'congue', 'praesent', 'sagittis', 'laoreet',
        'auctor', 'mauris', 'non', 'velit', 'eros', 'dictum',
        'proin', 'accumsan', 'sapien', 'nec', 'massa', 'volutpat',
        'venenatis', 'sed', 'eu', 'molestie', 'lacus', 'quisque',
        'porttitor', 'ligula', 'dui', 'mollis', 'tempus', 'at',
        'magna', 'vestibulum', 'turpis', 'ac', 'diam',
        'tincidunt', 'id', 'condimentum', 'enim', 'sodales', 'in',
        'hac', 'habitasse', 'platea', 'dictumst', 'aenean', 'neque',
        'fusce', 'augue', 'leo', 'eget', 'semper', 'mattis',
        'tortor', 'scelerisque', 'nulla', 'interdum', 'tellus',
        'malesuada', 'rhoncus', 'porta', 'sem', 'aliquet',
        'et', 'nam', 'suspendisse', 'potenti', 'vivamus', 'luctus',
        'fringilla', 'erat', 'donec', 'justo', 'vehicula',
        'ultricies', 'varius', 'ante', 'primis', 'faucibus', 'ultrices',
        'posuere', 'cubilia', 'curae', 'etiam', 'cursus',
        'aliquam', 'quam', 'dapibus', 'nisl', 'feugiat', 'egestas',
        'class', 'aptent', 'taciti', 'sociosqu', 'ad', 'litora',
        'torquent', 'per', 'conubia', 'nostra', 'inceptos', 'himenaeos',
        'phasellus', 'nibh', 'pulvinar', 'vitae', 'urna', 'iaculis',
        'lobortis', 'nisi', 'viverra', 'arcu', 'morbi', 'pellentesque',
        'metus', 'commodo', 'ut', 'facilisis', 'felis',
        'tristique', 'ullamcorper', 'placerat', 'aenean', 'convallis',
        'sollicitudin', 'integer', 'rutrum', 'duis', 'est',
        'etiam', 'bibendum', 'donec', 'pharetra', 'vulputate', 'maecenas',
        'mi', 'fermentum', 'consequat', 'suscipit', 'aliquam',
        'habitant', 'senectus', 'netus', 'fames', 'quisque',
        'euismod', 'curabitur', 'lectus', 'elementum', 'tempor',
        'risus', 'cras'
    ];

    /**
     * A random firstname
     *
     * @return string
     */
    public static function firstname()
    {
        return self::$firstNames[array_rand(self::$firstNames)];
    }

    /**
     * A random lastname
     *
     * @return string
     */
    public static function lastname()
    {
        return self::$lastNames[array_rand(self::$lastNames)];
    }

    /**
     * A random name
     *
     * @return string
     */
    public static function name()
    {
        return self::firstname() . ' ' . self::lastname();
    }

    /**
     * A random boolean
     *
     * @return bool
     */
    public static function boolean()
    {
        return rand(0, 1) == 1;
    }

    /**
     * A random address
     *
     * @return array
     */
    public static function address()
    {
        return self::$addresses[array_rand(self::$addresses)];
    }

    /**
     * A random address part
     *
     * @param string $part Address, Street, StreetNumber, City, Postcode or Country
     * @return string
     */
    public static function addressPart($part)
    {
        $address = self::address();
        $rpart = $part;
        if ($part == 'Street' || $part == 'StreetNumber') {
            $rpart = 'Address';
        }
        $v = $address[$rpart];
        if ($part == 'Street' || $part == 'StreetNumber') {
            $vex = explode(' ', $v);
            if ($part == 'Street') {
                array_shift($vex);
                $v = implode(' ', $vex);
            }
            if ($part == 'StreetNumber') {
                $v = $vex[0];
            }
        }
        return $v;
    }

    /**
     * Random float point value
     *
     * @param int $intMin
     * @param int $intMax
     * @param int $intDecimals
     * @return float
     */
    public static function fprand($intMin, $intMax, $intDecimals)
    {
        if ($intDecimals) {
            $intPowerTen = pow(10, $intDecimals);
            return rand($intMin, $intMax * $intPowerTen) / $intPowerTen;
        } else {
            return rand($intMin, $intMax);
        }
    }

    /**
     * A randomized position
     *
     * @param float $latitude
     * @param float $longitude
     * @param int $radius
     * @return array
     */
    public static function latLon($latitude = null, $longitude = null, $radius = 20)
    {
        if ($latitude === null) {
            $latitude = self::$latitude;
        }
        if ($longitude === null) {
            $longitude = self::$longitude;
        }
        $lng_min = $longitude - $radius / abs(cos(deg2rad($latitude)) * 69);
        $lng_max = $longitude + $radius / abs(cos(deg2rad($latitude)) * 69);
        $lat_min = $latitude - ($radius / 69);
        $lat_max = $latitude + ($radius / 69);

        $rand = self::fprand(0, ($lng_max - $lng_min), 3);
        $lng = $lng_min + $rand;
        $rand = self::fprand(0, ($lat_max - $lat_min), 3);
        $lat = $lat_min + $rand;

        return compact('lat', 'lng', 'lng_min', 'lat_min', 'lng_max', 'lat_max');
    }

    /**
     * A random domain
     * @return string
     */
    public static function domain()
    {
        return self::$domains[array_rand(self::$domains)];
    }

    /**
     * A random website
     *
     * @return string
     */
    public static function website()
    {
        return 'http://www' . self::domain();
    }

    public static function adorableAvatar($size = 200, $id = null)
    {
        if (!$id) {
            $id = uniqid();
        }
        $result = file_get_contents('https://api.adorable.io/avatars/' . $size . '/' . $id);

        return self::storeFakeImage($result, $id . '.png', 'Adorable');
    }

    /**
     * Generate some random users
     *
     * @link https://randomuser.me/documentation
     * @param array $params
     * @param bool $useDefaultParams
     * @return array
     */
    public static function randomUser($params = [], $useDefaultParams = true)
    {
        $defaultParams = [
            'results' => '20',
            'password' => 'upper,lower,8-12',
            'nat' => 'fr,us,gb,de',
        ];

        if ($useDefaultParams) {
            $params = array_merge($defaultParams, $params);
        }
        $result = file_get_contents('https://randomuser.me/api/?' . http_build_query($params));

        $data = json_decode($result, JSON_OBJECT_AS_ARRAY);

        if (!empty($data['error'])) {
            throw new Exception($data['error']);
        }

        return $data['results'];
    }

    /**
     * Store a fake image
     *
     * @param string $data
     * @param string $name The filename, including extension
     * @param string $folder The sub folder where the fake image is stored (default folder is Faker/Uploads)
     * @return Image
     */
    public static function storeFakeImage($data, $name, $folder = 'Uploads')
    {
        $filter = new FileNameFilter;
        $name = $filter->filter($name);

        $folderName = self::$folder . '/' . $folder;
        $filename = $folderName . '/' . $name;

        $image = new Image;
        $image->setFromString($data, $filename);
        $image->write();

        return $image;
    }

    /**
     * Get a random image
     *
     * Images are generated only once, if folder does not exists in assets
     *
     * @return Image
     */
    public static function image()
    {
        $path = self::$folder . '/Images';
        $images = Image::get()->where("FileFilename LIKE '$path%'");
        if ($images->count() <= 0) {
            $folder = Folder::find_or_make($path);
            foreach (range(1, 30) as $i) {
                $data = file_get_contents("https://picsum.photos/id/$i/1920/1080");
                $filename = "$path/fake-$i.jpg";
                $imageObj = new Image;
                $imageObj->setFromString($data, $filename);
                $imageObj->write();

                // publish this stuff!
                if ($imageObj->isInDB() && !$imageObj->isPublished()) {
                    $imageObj->publishSingle();
                }
            }
            $images = Image::get()->where("FileFilename LIKE 'Faker/Images%'");
        }
        $rand = rand(0, count($images));
        foreach ($images as $key => $image) {
            if ($key == $rand) {
                // publish this stuff!
                if ($image->isInDB() && !$image->isPublished()) {
                    $image->publishSingle();
                }

                return $image;
            }
        }
        return $images->First();
    }

    /**
     * Get a random image unique for a record that can be deleted without side effects
     * for other records
     *
     * @param DataObject $record
     * @return Image
     */
    public static function ownImage($record)
    {
        $path = self::$folder;
        if ($record->hasMethod('getFolderName')) {
            $path = $record->getFolderName();
            $path .= "/" . self::$folder;
        }

        $i = rand(1, 1080);
        $data = file_get_contents("https://picsum.photos/id/$i/1920/1080");

        $name =  "fake-$i.jpg";
        $filename = "$path/$name";

        $image = new Image;
        $image->setFromString($data, $filename);
        $image->write();

        return $image;
    }

    /**
     * Get a random record
     *
     * @param string $class
     * @param array $filters
     * @return DataObject
     */
    public static function record($class, $filters = [])
    {
        $q = $class::get()->sort('RAND()');
        if (!empty($filters)) {
            $q = $q->filter($filters);
        }
        return $q->first();
    }

    /**
     * Get random words
     *
     * @param int $num
     * @param int $num2
     * @return string
     */
    public static function words($num, $num2 = null)
    {
        $res = [];
        $i = 0;
        $total = $num;
        if ($num2 !== null) {
            $i = rand(0, $num);
            $total = $num2;
        }
        $req = $total - $i;
        foreach (array_rand(self::$words, $req) as $key) {
            $res[] = self::$words[$key];
        }
        return implode(' ', $res);
    }

    /**
     * Get random sentences
     *
     * @param int $num
     * @param int $num2
     * @return string
     */
    public static function sentences($num, $num2 = null)
    {
        $res = [];
        $i = 0;
        $total = $num;
        if ($num2 !== null) {
            $i = rand(0, $num);
            $total = $num2;
        }
        $req = $total - $i;
        while ($req--) {
            $res[] = self::words(5, 10);
        }
        return implode(".\n", $res);
    }

    /**
     * Get random paragraphs
     *
     * @param int $num
     * @param int $num2
     * @return string
     */
    public static function paragraphs($num, $num2 = null)
    {
        $res = [];
        $i = 0;
        $total = $num;
        if ($num2 !== null) {
            $i = rand(0, $num);
            $total = $num2;
        }
        $req = $total - $i;
        while ($req--) {
            $res[] = "<p>" . self::sentences(3, 7) . "</p>";
        }
        return implode("\n", $res);
    }

    /**
     * Get a date between two dates
     *
     * @param string $num
     * @param string $num2
     * @param string $format
     * @return string
     */
    public static function date($num, $num2, $format = 'Y-m-d H:i:s')
    {
        if (is_string($num)) {
            $num = strtotime($num);
        }
        if (is_string($num2)) {
            $num2 = strtotime($num2);
        }
        $rand = rand($num, $num2);
        return date($format, $rand);
    }

    /**
     * Male or female
     *
     * @return string
     */
    public static function male_or_female()
    {
        return self::pick(['male', 'female']);
    }

    /**
     * Randomly pick in an array
     *
     * @param array $arr
     * @return array
     */
    public static function pick(array $arr)
    {
        return $arr[array_rand($arr)];
    }

    /**
     * Get a random country
     *
     * @return string
     */
    public static function country()
    {
        $countries = array_values(CountriesList::get());
        return $countries[array_rand($countries)];
    }

    /**
     * Get a random country code
     *
     * @return string
     */
    public static function countryCode()
    {
        $countries = array_keys(CountriesList::get());
        return $countries[array_rand($countries)];
    }
}
