# Wordpress Consent Tools Server & Administration

_A backend for the consent-tools library in Wordpress._

---

 - [Installation](#installation)
 - [Usage](#usage)

## Installation

via composer:
```shell
> composer require sillynet/wp-consent-tools
```

Make sure you have Composer autoload or an alternative class loader present.

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

