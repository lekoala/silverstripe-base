<?php
namespace LeKoala\Base\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\FieldList;

class SocialExtension extends DataExtension
{
    //TODO: find a more flexible way to deal with various type of social networks
    private static $db = [
        "Facebook" => "Varchar(191)",
        "Twitter" => "Varchar(191)",
        "LinkedIn" => "Varchar(191)",
        "Youtube" => "Varchar(191)",
        "Vimeo" => "Varchar(191)",
        "Flickr" => "Varchar(191)",
        "Instagram" => "Varchar(191)",
        "Pinterest" => "Varchar(191)",
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

    public function FacebookShareUrl()
    {
        $link = $this->owner->Link();
        return 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode(Director::absoluteURL($link));
    }

    public function TwitterShareUrl()
    {
        $link = $this->owner->Link();
        return 'http://twitter.com/share?url=' . urlencode(Director::absoluteURL($link)) . '&text=' . urlencode($this->owner->Title);
    }

    public function EmailLink()
    {
        $link = $this->owner->Link();
        $body = _t('SocialExtension.DISCOVER', 'I discovered ') . ' "' . $this->owner->Title . '" \n' .
            _t('SocialExtension.SEE', 'You can see it here :') . ' ' . Director::absoluteURL($link);
        return 'mailto:?subject=' . $this->owner->Title . '&body=' . htmlentities($body);
    }
}