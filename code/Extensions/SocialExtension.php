<?php
namespace LeKoala\Base\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\FieldList;

/**
 * Class \LeKoala\Base\Extensions\SocialExtension
 *
 * @property \SilverStripe\SiteConfig\SiteConfig|\LeKoala\Base\Extensions\SocialExtension $owner
 * @property string $Facebook
 * @property string $Twitter
 * @property string $LinkedIn
 * @property string $Youtube
 * @property string $Vimeo
 * @property string $Flickr
 * @property string $Instagram
 * @property string $Pinterest
 */
class SocialExtension extends DataExtension
{
    //TODO: find a more flexible way to deal with various type of social networks
    private static $db = [
        "Facebook" => "Varchar(59)",
        "Twitter" => "Varchar(59)",
        "LinkedIn" => "Varchar(59)",
        "Youtube" => "Varchar(59)",
        "Vimeo" => "Varchar(59)",
        "Flickr" => "Varchar(59)",
        "Instagram" => "Varchar(59)",
        "Pinterest" => "Varchar(59)",
    ];
    public function updateCMSFields(FieldList $fields)
    {
        // $tab = new \SilverStripe\Forms\Tab('Social');
        // $fields->addFieldToTab('Root',$tab);
        $headerField = new \SilverStripe\Forms\HeaderField('SocialAccountsHeader', "Social Accounts");
        $fields->addFieldToTab('Root.Main', $headerField);
        foreach (self::$db as $name => $type) {
            $field = new \SilverStripe\Forms\TextField($name, $this->owner->fieldLabel($name));
            // $tab->push($field);
            $fields->addFieldToTab('Root.Main', $field);
        }
    }
    public function FacebookLink()
    {
        return 'https://www.facebook.com/' . $this->owner->Facebook;
    }
    public function TwitterLink()
    {
        return 'https://twitter.com/' . $this->owner->Twitter;
    }
    public function LinkedInLink()
    {
        return 'https://www.linkedin.com/' . $this->owner->LinkedIn;
    }
    public function YoutubeLink()
    {
        return 'https://www.youtube.com/user/' . $this->owner->Youtube;
    }
    public function VimeoLink()
    {
        return 'https://vimeo.com/' . $this->owner->Vimeo;
    }
    public function InstagramLink()
    {
        return 'https://www.instagram.com/' . $this->owner->Instagram;
    }
    public function FlickrLink()
    {
        return 'https://www.flickr.com/photos/' . $this->owner->Flickr;
    }
    public function PinterestLink()
    {
        return 'https://www.pinterest.com/' . $this->owner->Pinterest;
    }
}
