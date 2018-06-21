# SilverStripe 4 Base

By default, SilverStripe lack some really common stuff I find myself doing in most of my websites.
This module aims to provides basic functionnalities that can be easily used on a website.

This module is under heavy work in progress, things breaks and will change. Use at your own risks.

## Requirements

* SilverStripe ^4.1

## Installation

You can install this module with Composer:

```
composer require lekoala/silverstripe-base
```

---

## Features

### Common pages

- Contact Page with Google Map support
- FAQ Page
- Simple News system (if you need a more complex solution, use the Blog module)

### Block pages

Allows to modularize pages with "blocks" content. This aims to be a simple alternative to elemental module.

All the content is saved into the Content field and therefore can be used with no performance penality.

### Extended DataObject actions

If you find that adding the "betterbuttons" module just to add a couple of actions and a Save and Close functionnality
on your DataObject is too much, I got you covered.

Simply use default getCMSActions and use my new CustomAction class!

Also refactor basic UI stuff that SilverStripe should already do (like having the delete button on the right).

See [docs/Actions.md](docs/Actions.md) for documentation.

### Themable sites

Make sites themable through the SiteConfig and offer support for variables in your css files.

See [docs/Theming.md](docs/Theming.md) for documentation.

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
