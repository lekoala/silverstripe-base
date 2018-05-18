<?php
namespace LeKoala\Base\Theme;

use SilverStripe\Forms\Tab;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Control\Director;
use LeKoala\Base\Forms\ColorField;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\HeaderField;
use SilverStripe\ORM\DataExtension;
use LeKoala\Base\Helpers\ZipHelper;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\DropdownField;
use LeKoala\Base\ORM\FieldType\Color;

/**
 * Extend SiteConfig to make your website Themable
 *
 * @property \SilverStripe\SiteConfig\SiteConfig|\LeKoala\Base\Theme\ThemeSiteConfigExtension $owner
 * @property string $PrimaryColor
 * @property string $SecondaryColor
 * @property string $ThemeColor
 * @property string $HeaderFontFamily
 * @property int $HeaderFontWeight
 * @property string $BodyFontFamily
 * @property int $BodyFontWeight
 * @property string $GoogleFonts
 * @property int $LogoID
 * @property int $IconID
 * @property int $FaviconID
 * @method \SilverStripe\Assets\Image Logo()
 * @method \SilverStripe\Assets\Image Icon()
 * @method \SilverStripe\Assets\File Favicon()
 */
class ThemeSiteConfigExtension extends DataExtension
{
    use KnowsThemeDir;
    private static $db = [
        "PrimaryColor" => Color::class,
        "SecondaryColor" => Color::class,
        "ThemeColor" => Color::class,
        "MaskColor" => Color::class,
        "HeaderFontFamily" => "Varchar(59)",
        "HeaderFontWeight" => "Int",
        "BodyFontFamily" => "Varchar(59)",
        "BodyFontWeight" => "Int",
        "CssTheme" => "Varchar(59)",
        "GoogleFonts" => "Varchar(99)",
    ];
    private static $has_one = [
        "Logo" => Image::class,
        "Icon" => Image::class,
        "Favicon" => File::class,
    ];
    private static $owns = [
        "Logo",
        "Icon",
    ];
    public function onAfterWrite()
    {
        if ($this->owner->LogoID) {
            $Logo = $this->owner->Logo();
            if (!$Logo->isPublished()) {
                $Logo->doPublish();
            }
        }
        if ($this->owner->IconID) {
            $Icon = $this->owner->Icon();
            if (!$Icon->isPublished()) {
                $Icon->doPublish();
            }
        }
    }
    /**
     * Get all font weight with a human readable value
     *
     * @return array
     */
    public static function listFontWeights()
    {
        return [
            100 => 'thin',
            200 => 'extra-light',
            300 => 'light',
            400 => 'regular',
            500 => 'medium',
            600 => 'semi-bold',
            700 => 'bold',
            800 => 'extra-bold',
            900 => 'black',
        ];
    }
    /**
     * List all *-theme.css files in current theme
     *
     * @return array
     */
    public function listCssThemes()
    {
        $cssPath = $this->getThemeCssPath();
        $files = glob($cssPath . '/*-theme.css');
        $arr = [];
        foreach ($files as $file) {
            $name = basename($file);
            $arr[$name] = str_replace('-theme.css', '', $name);
        }
        return $arr;
    }
    /**
     * Get where your css files are stored
     *
     * @return string
     */
    public function getThemeCssPath()
    {
        $themeDir = $this->getThemeDir();
        return Director::baseFolder() . '/' . $themeDir . '/css';
    }
    /**
     * List options defined in current css theme if any
     *
     * Supports options declared in comments
     * @disallowFonts
     * @disallowColors
     *
     * @return array
     */
    public function currentThemeOptions()
    {
        $values = [
            'allowFonts' => true,
            'allowColors' => true,
        ];
        if (!$this->owner->CssTheme) {
            return $values;
        }
        $themeFile = $this->getThemeCssPath() . '/' . $this->owner->CssTheme;
        $contents = file_get_contents($themeFile);
        if (strpos($contents, '@disallowFonts') !== false) {
            $values['allowFonts'] = false;
        }
        if (strpos($contents, '@disallowColors') !== false) {
            $values['allowColors'] = false;
        }
        return $values;
    }
    public function updateCMSFields(FieldList $fields)
    {
        $themeTab = new Tab("Theme");
        $fields->addFieldToTab('Root', $themeTab);
        // If we have themes, allow to configure some css variables in them
        $cssThemes = $this->listCssThemes();
        $themeOptions = $this->currentThemeOptions();
        if (!empty($cssThemes)) {
            // Colors
            if ($themeOptions["allowColors"]) {
                $ColorsHeader = new HeaderField("ColorsHeader", "Colors");
                $themeTab->push($ColorsHeader);
                $ColorsGroup = new FieldGroup();
                $themeTab->push($ColorsGroup);
                $PrimaryColor = new ColorField('PrimaryColor');
                $ColorsGroup->push($PrimaryColor);
                $SecondaryColor = new ColorField('SecondaryColor');
                $ColorsGroup->push($SecondaryColor);
                $ThemeColor = new ColorField('ThemeColor');
                $ColorsGroup->push($ThemeColor);
                $MaskColor = new ColorField('MaskColor');
                $ColorsGroup->push($MaskColor);
            }
            // Fonts
            if ($themeOptions["allowFonts"]) {
                $FontsHeader = new HeaderField("FontsHeader", "Fonts");
                $themeTab->push($FontsHeader);
                $FontsGroup = new FieldGroup();
                $themeTab->push($FontsGroup);
                $HeaderFont = new TextField("HeaderFontFamily");
                $FontsGroup->push($HeaderFont);
                $HeaderFontWeight = new DropdownField("HeaderFontWeight", $this->owner->fieldLabel('HeaderFontWeight'), self::listFontWeights());
                $HeaderFontWeight->setHasEmptyDefault(true);
                $FontsGroup->push($HeaderFontWeight);
                $BodyFont = new TextField("BodyFontFamily");
                $FontsGroup->push($BodyFont);
                $BodyFontWeight = new DropdownField("BodyFontWeight", $this->owner->fieldLabel('BodyFontWeight'), self::listFontWeights());
                $BodyFontWeight->setHasEmptyDefault(true);
                $FontsGroup->push($BodyFontWeight);
                $GoogleFonts = new TextField("GoogleFonts");
                $GoogleFonts->setAttribute('placeholder', "Open+Sans|Roboto");
                $themeTab->push($GoogleFonts);
            }
            // Theme - only if any is available
            if (!empty($cssThemes)) {
                $CssTheme = new DropdownField("CssTheme", $this->owner->fieldLabel('CssTheme'), $cssThemes);
                $CssTheme->setHasEmptyDefault(true);
                $themeTab->push($CssTheme);
            }
        }
        //
        $ImagesHeader = new HeaderField("ImagesHeader", "Images");
        $themeTab->push($ImagesHeader);
        /* @var $Logo UploadField */
        $Logo = UploadField::create("Logo");
        $Logo->setFolderName("Theme");
        $Logo->setAllowedFileCategories("image/supported");
        $themeTab->push($Logo);
        $Icon = UploadField::create("Icon");
        $Icon->setFolderName("Theme");
        $Icon->setAllowedFileCategories("image/supported");
        $themeTab->push($Icon);
        /* @var $Favicon UploadField */
        $Favicon = UploadField::create("Favicon");
        $Favicon->setFolderName("Theme");
        $Favicon->setAllowedExtensions('zip');
        $Favicon->setDescription("Upload the zip file generated with <a href=\"https://realfavicongenerator.net/\" target=\"_blank\">Real Favicon Generator</a>. Theme Color will be used as background for your icon.");
        $themeTab->push($Favicon);
    }
    /**
     * Render your favicons with proper markup
     *
     * @param string $path
     * @return void
     */
    public function Favicons($path = '')
    {
        $path = preg_replace('/\/+/', '/', Director::baseURL() . $path . '/');
        // A mask color, used by macOS safari and touch bar (should look good with a white icon)
        $mask = $this->owner->MaskColor ? $this->owner->MaskColor : '#000000';
        // A contrast color for the icon, used by Windows Metro and Android
        $theme = $this->owner->ThemeColor ? $this->owner->ThemeColor : '#ffffff';
        return $this->owner->customise(
            array(
                'Path' => $path,
                'ThemeColor' => $theme,
                'MaskColor' => $mask,
            )
        )->renderWith('Favicons');
    }
    public function getThemeAssetURL()
    {
        return '/assets/_theme/' . $this->owner->ID;
    }
    public function getThemeAssetsFolder()
    {
        $dir = Director::publicFolder() . $this->getThemeAssetURL();
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }
    public function onBeforeWrite()
    {
        $changedFields = $this->owner->getChangedFields(true, 2);
        if (isset($changedFields['FaviconID'])) {
            try {
                $this->unpackFaviconArchive();
                $dir = $this->getThemeAssetsFolder();
                $webmanifest = is_file($dir . '/site.webmanifest');
                if (is_file($webmanifest)) {
                    $this->parseWebManifest($webmanifest);
                }
            } catch (Exception $ex) {
            }
        }
    }
    /**
     * Assign theme color from webmanifest
     *
     * @param string $file
     * @return void
     */
    protected function parseWebManifest($file)
    {
        $data = file_get_contents($file);
        $arr = json_decode($data);
        $this->owner->ThemeColor = $arr['theme_color'];
    }
    /**
     * Unpack a favicon archive into theme asset folder
     *
     * @return void
     */
    protected function unpackFaviconArchive()
    {
        /* @var $Favicon File */
        $Favicon = $this->owner->Favicon();
        $FaviconData = $Favicon->getString();
        $tmpName = tempnam(TEMP_PATH, 'ss');
        file_put_contents($tmpName, $FaviconData);
        $dir = $this->getThemeAssetsFolder();
        ZipHelper::unzipTo($tmpName, $dir);
    }
}
