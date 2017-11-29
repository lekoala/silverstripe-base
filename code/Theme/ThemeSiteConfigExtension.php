<?php
namespace LeKoala\Base\Theme;

use SilverStripe\Forms\Tab;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\FieldList;
use TractorCow\Colorpicker\Color;
use SilverStripe\Forms\TextField;
use SilverStripe\Control\Director;
use LeKoala\Base\Helpers\ZipHelper;
use SilverStripe\Forms\HeaderField;
use SilverStripe\ORM\DataExtension;
use TractorCow\Colorpicker\Forms\ColorField;
use SilverStripe\AssetAdmin\Forms\UploadField;

/**
 *
 */
class ThemeSiteConfigExtension extends DataExtension
{
    private static $db = [
        "PrimaryColor" => Color::class,
        "SecondaryColor" => Color::class,
        "ThemeColor" => Color::class,
        "HeaderFont" => "Varchar",
        "BodyFont" => "Varchar",
        "GoogleFonts" => "Varchar",
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
        if($this->owner->LogoID) {
            $Logo = $this->owner->Logo();
            if(!$Logo->isPublished()) {
                $Logo->doPublish();
            }
        }
        if($this->owner->IconID) {
            $Icon = $this->owner->Icon();
            if(!$Icon->isPublished()) {
                $Icon->doPublish();
            }
        }
    }

    public function updateCMSFields(FieldList $fields)
    {
        $themeTab = new Tab("Theme");
        $fields->addFieldToTab('Root', $themeTab);

        $ColorsHeader = new HeaderField("ColorsHeader", "Colors");
        $themeTab->push($ColorsHeader);

        $PrimaryColor = new ColorField('PrimaryColor');
        $themeTab->push($PrimaryColor);

        $SecondaryColor = new ColorField('SecondaryColor');
        $themeTab->push($SecondaryColor);

        $ThemeColor = new ColorField('ThemeColor');
        $ThemeColor->setDescription("Select a color that gives a good contrast with your Icon");
        $themeTab->push($ThemeColor);

        $FontsHeader = new HeaderField("FontsHeader", "Fonts");
        $themeTab->push($FontsHeader);

        $HeaderFont = new TextField("HeaderFont");
        $themeTab->push($HeaderFont);

        $BodyFont = new TextField("BodyFont");
        $themeTab->push($BodyFont);

        $GoogleFonts = new TextField("GoogleFonts");
        $GoogleFonts->setAttribute('placeholder', "Open+Sans|Roboto");
        $themeTab->push($GoogleFonts);

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

    public function Favicons($path = '')
    {
        $path = preg_replace('/\/+/', '/', Director::baseURL() . $path . '/');

        // A mask color, used by macOS safari and touch bar (should look good with a white icon)
        $mask = $this->owner->PrimaryColor ? '#' . $this->owner->PrimaryColor : '#000000';

        // A contrast color for the icon, used by Windows Metro and Android
        $theme =  $this->owner->ThemeColor ? '#' . $this->owner->ThemeColor : '#ffffff';
        return $this->owner->customise(
            array(
                'Path' => $path,
                'ThemeColor' => $theme,
                'MaskColor' => $mask,
            )
        )->renderWith('Favicons');
    }

    public function getThemeAssetsDir()
    {
        $dir = Director::baseFolder() . '/assets/_theme';
        if (!is_dir($dir)) {
            mkdir($dir, 0755);
        }
        return $dir;
    }

    public function onBeforeWrite()
    {
        $changedFields = $this->owner->getChangedFields(true, 2);

        if (isset($changedFields['FaviconID'])) {
            $this->unpackFaviconArchive();
        }
    }

    protected function unpackFaviconArchive()
    {
        /* @var $Favicon File */
        $Favicon = $this->owner->Favicon();
        $FaviconData = $Favicon->getString();

        $tmpName = \tempnam(TEMP_PATH, 'ss');
        \file_put_contents($tmpName, $FaviconData);

        $dir = $this->getThemeAssetsDir();

        $ZipArchive = new \ZipArchive;
        $res = $ZipArchive->open($tmpName);

        if ($res === true) {
            $ZipArchive->extractTo($dir);
            $ZipArchive->close();
        } else {
            die('failed : ' . ZipHelper::getErrorMessage($res));
        }
    }
}
