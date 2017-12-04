<?php
namespace LeKoala\Base\Extensions;

use SilverStripe\Forms\FieldList;
use SilverStripe\Control\Director;
use SilverStripe\ORM\DataExtension;

/**
 * @link http://www.sharelinkgenerator.com/
 */
class SocialShareExtension extends DataExtension
{

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

    public function GooglePlusShareUrl()
    {
        $link = $this->owner->Link();
        return 'https://plus.google.com/share?url=' . urlencode(Director::absoluteURL($link));
    }

    public function LinkedInShareUrl()
    {
        $link = $this->owner->Link();
        return 'https://www.linkedin.com/shareArticle?mini=true&url=' . urlencode(Director::absoluteURL($link));
    }

    public function EmailShareLink()
    {
        $link = $this->owner->Link();
        $body = _t('SocialExtension.DISCOVER', 'I discovered ') . ' "' . $this->owner->Title . '" \n' .
            _t('SocialExtension.SEE', 'You can see it here :') . ' ' . Director::absoluteURL($link);
        return 'mailto:?subject=' . $this->owner->Title . '&body=' . htmlentities($body);
    }
}
