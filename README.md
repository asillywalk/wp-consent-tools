# Wordpress Consent Tools Server & Administration for Adretto

_A backend for the consent-tools library in Wordpress using Adretto._

---

 - [Installation](#installation)
 - [Usage](#usage)

## Installation

via composer:
```shell
> composer require sillynet/adretto-consent-tools
```

This library is an extension to the [Adretto](https://github.com/asillywalk/adretto)
Wordpress ADR framework, so you will need to have Adretto installed and set up.

Make sure you have Composer autoload or an alternative class loader present.

### Carbon Fields

This packages uses [CarbonFields](https://docs.carbonfields.net/) to generate
the settings page. Unfortunately CarbonFields can be somewhat tricky to set up
when used outside a theme's root directory.
**If your theme files live in `public/wp-content/themes/<yourtheme>/`, and you
have your composer.json and `vendor/` directory there, you're fine.**

If you however maintain your Composer dependencies outside of the theme 
directory, or use some symlink setup, you might be in trouble. The simplest
solution is to add a step to your setup and build process to make CF's JS and 
CSS assets publicly available at a known path:

1. As part of your build pipeline, copy the entire `vendor/htmlburger/carbon-fields`
   directory into `public/wp-content` (or you could copy just the `*.js` and
   `*.css` files, but you'll have to keep the directory structure). A simple
   solution is to actually use your theme directory, so let's use
   `public/wp-content/themes/<mytheme>/vendor/cf` as an example.
2. Tell CarbonFields where it can find its assets by setting a constant _before_
   CF is "booted", somewhere near the top of your `functions.php` would work
   fine in most scenarios: 
   ```
   define('Carbon_Fields\DIR', get_theme_file_path('vendor/cf'));
   ```


## Usage

Load the extension in your Adretto configuration file:

```yaml
# .config.yaml
extensions:
  - Sillynet\\ConsentTools\\ConsentToolsExtension
```

You will find a settings page under the Wordpress general settings tab where 
you can configure the consent management services for
[`@gebruederheitz/consent-tools`](https://www.npmjs.com/package/@gebruederheitz/consent-tools).

When setting up _consent-tools_ you can retrieve the configuration via
`/wp-json/sillynet/v1/consent-management/config?lang=en`, which will return an
object with the following shape:

```typescript
type ServiceConfig = {
    // regular fields
    prettyName?: string;           // The pretty name for the service as it
                                   // should be shown to user, in placeholder
                                   // templates or settings modals
    cmpServiceId?: string;         // The ID of this service as defined by
                                   // by the CMP used. If you're running
                                   // consent-tools in standalone mode,
                                   // this can simply be ignored.
    privacyPolicySection?: string; // An anchor for linking to this specific
                                   // service's section on the privacy policy
                                   // page.
    
    // translated fields, these will differ based on the requested language
    // ("en" in this example)
    titleText?: string;             // override for default titleText below
    buttonText?: string;            // override for default buttonText below
    description?: string;           // override for default description below
}

type ConsentToolsConfig = {
    default: {
        titleText: string;          // Default text to be displayed in the
                                    // placeholder element's heading if not
                                    // overridden by the service's config.
        description: string;        // Default text to be displayed in the
                                    // placeholder element's body. May contain
                                    // %templateTags%.
        buttonText: string;         // Default text to be displayed in the
                                    // placeholder element's "consent" button.
    },
    types: {
        [serviceId: string]: ServiceConfig
    }
}
```

## Development

### Dependencies

- PHP >= 7.4
- [Composer 2.x](https://getcomposer.org)
- [NVM](https://github.com/nvm-sh/nvm) and nodeJS LTS (v16.x)
- Nice to have: GNU Make (or drop-in alternative)

### Makefile

Most everyday development tasks are covered in the Makefile.
