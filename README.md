# SilverStripe 4 Base

By default, SilverStripe lack some really common stuff I find myself doing in most of my websites.
This module aims to provides basic functionnalities that can be easily used on a website.

This module is under heavy work in progress, things breaks and will change. Use at your own risks.

## Requirements

* SilverStripe ^4.1
* Use public folder

## Installation

You can install this module with Composer:

```
composer require lekoala/silverstripe-base
```

If you install this as a git submodule don't forget to adjust your autoloader

    "autoload": {
        "psr-4": {
            "App\\": "app/src/",
            "LeKoala\\Base\\": "src/",
            "LeKoala\\Base\\Test\\": "tests/"
        },
        "classmap": [
            "app/src/Page.php",
            "app/src/PageController.php"
        ]
    },

Also you may need to adjust your default app/_config/mysite.yml to make sure base module is loaded first

    ---
    Name: myproject
    After:
    - '#base-extensions'
    ---
    SilverStripe\Core\Manifest\ModuleManifest:
        project: app
    SilverStripe\Control\Email\Email:
        admin_email: noreply@mydomain.com
    # If you use bootstrap 4
    SilverStripe\CMS\Model\SiteTree:
        extensions:
            - LeKoala\Base\Extensions\BootstrapPageExtension

This also applies to your theme.yml

    ---
    Name: mytheme
    After:
    - '#base-theme'
    ---

---

## Features

### Common pages

- Contact Page with Google Map support
- FAQ Page
- Simple News system (if you need a more complex solution, use the Blog module)

### Extended DataObject actions

Now provided by lekoala/silverstripe-cms-actions

### Themable sites

Make sites themable through the SiteConfig and offer support for variables in your css files.

See [docs/Theming.md](docs/Theming.md) for documentation.

### New or improved db field types

Need for phone, country, color fields? Yes!
Should your enums map labels to a static method? Yes!
Should your scaffolding use better input fields? Sure!
:-)

### Forms

Lots of new form fields.

See [docs/Forms.md](docs/Forms.md) for documentation.

### Extensions

- Social media
- Sortable
- Smart uploads

### Alerts

Define sessionMessage on your controller and display messages using Alertify library

### Dev tools

A few useful tools to make your DX more pleasant.

See [docs/Dev.md](docs/Dev.md) for documentation.

## Maintainer

LeKoala - thomas@lekoala.be

## License

This module is licensed under the [MIT license](LICENSE).
