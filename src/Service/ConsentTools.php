<?php

namespace Sillynet\ConsentTools\Service;

use Carbon_Fields\Container;
use Carbon_Fields\Field;
use Psr\Container\ContainerInterface;
use Sillynet\Adretto\Contracts\Translator;
use Sillynet\Adretto\Theme;
use Sillynet\ConsentTools\ConfigFields;

use function carbon_get_theme_option;

class ConsentTools
{
    /**
     * Prefix for theme option fields
     */
    protected const PREF = 'sn_consent_management_';

    protected string $textDomain = 'sillynet';
    protected Translator $translator;
    protected Theme $theme;

    public function __construct(
        Translator $translator,
        Theme $theme,
        ContainerInterface $container
    ) {
        $this->translator = $translator;
        $this->theme = $theme;
        // @FIXME: can we make this more explicit – via a contract maybe?
        $textDomain = $container->get('textDomain');
        if (!empty($textDomain)) {
            $this->textDomain = $textDomain;
        }
    }

    /**
     * @return array{default: array<string, string>, types: array<array<string,mixed>>}
     */
    public function getConsentToolsConfig(string $lang): array
    {
        return [
            'default' => [
                'privacyPolicyUrl' => $this->getPrivacyPolicyUrl($lang),
                'titleText' => $this->getFieldValue(
                    ConfigFields::DEFAULT_TITLE_TEXT,
                    $lang,
                ),
                'description' => $this->getFieldValue(
                    ConfigFields::DEFAULT_DESCRIPTION,
                    $lang,
                ),
                'buttonText' => $this->getFieldValue(
                    ConfigFields::DEFAULT_BUTTON_TEXT,
                    $lang,
                ),
            ],
            'types' => $this->getMappedServices($lang),
        ];
    }

    public function registerSettingsPage()
    {
        $idFieldName = $this->getFieldName(ConfigFields::SERVICE_ID);
        $prettyNameFieldName = $this->getFieldName(
            ConfigFields::SERVICE_PRETTY_NAME,
        );

        /** @var Container\Theme_Options_Container $container */
        $container = Container::make(
            'theme_options',
            __('Consent Management', $this->textDomain),
        );
        $container->set_page_parent('options-general.php');

        $serviceField = Field::make_complex(
            $this->getFieldName(ConfigFields::SERVICES),
            __('Consent Management Services', $this->textDomain),
        )->set_layout('tabbed-vertical');

        $serviceFieldFields = [
            Field::make_text(
                $idFieldName,
                __('Service ID', $this->textDomain),
            )->set_required(),
            Field::make_text(
                $prettyNameFieldName,
                __('Pretty Name', $this->textDomain),
            ),
            Field::make_text(
                $this->getFieldName(ConfigFields::SERVICE_CMP_SERVICE_ID),
                __('CMP Service ID', $this->textDomain),
            )->set_help_text(
                'Set this value if your CMP uses a different ID for this service/vendor than the above.',
            ),
            Field::make_text(
                $this->getFieldName(
                    ConfigFields::SERVICE_PRIVACY_POLICY_SECTION,
                ),
                __('Privacy Policy Section', $this->textDomain),
            )->set_help_text(
                'If you want the placeholder templates to link to a specific section inside the privacy policy page, enter the anchor element\'s ID here (without the leading hash "#" character).',
            ),
        ];

        $container->add_tab(__('Services', $this->textDomain), [$serviceField]);

        foreach ($this->translator->getAllLanguages() as $language) {
            $lang = $language['slug'];

            $serviceFieldFields = array_merge($serviceFieldFields, [
                Field::make_separator(
                    'sep-' . $lang,
                    __('Placeholder', $this->textDomain) .
                        ' ' .
                        $language['name'],
                ),
                Field::make_textarea(
                    $this->getFieldName(
                        ConfigFields::SERVICE_DESCRIPTION,
                        $lang,
                    ),
                    __('Placeholder Description', $this->textDomain) .
                        ' (' .
                        $lang .
                        ')',
                )->set_rows(3),
                Field::make_text(
                    $this->getFieldName(
                        ConfigFields::SERVICE_TITLE_TEXT,
                        $lang,
                    ),
                    __('Placeholder Heading', $this->textDomain) .
                        ' (' .
                        $lang .
                        ')',
                ),
                Field::make_text(
                    $this->getFieldName(
                        ConfigFields::SERVICE_BUTTON_TEXT,
                        $lang,
                    ),
                    __('Placeholder Button Text', $this->textDomain) .
                        ' (' .
                        $lang .
                        ')',
                ),
            ]);

            $container->add_tab(
                __('Defaults', $this->textDomain) . ' (' . $lang . ')',
                [
                    Field::make_textarea(
                        $this->getFieldName(
                            ConfigFields::DEFAULT_DESCRIPTION,
                            $lang,
                        ),
                        __(
                            'Default Placeholder Description',
                            $this->textDomain,
                        ),
                    )
                        ->set_default_value(
                            'Um diesen Inhalt anzuzeigen, müssen Sie ihn durch Klick auf den Button aktivieren. Dadurch können Informationen an den Diensteanbieter übermittelt und dort gespeichert werden. Weitere Informationen finden Sie in unserer <a href="%privacyPolicyUrl%" target="_blank">Datenschutzerklärung</a>.',
                        )
                        ->set_rows(3),
                    Field::make_text(
                        $this->getFieldName(
                            ConfigFields::DEFAULT_TITLE_TEXT,
                            $lang,
                        ),
                        __('Default Placeholder Heading', $this->textDomain),
                    )->set_default_value(
                        'Wir benötigen ihr Einverständnis um externe Dienste zu laden.',
                    ),
                    Field::make_text(
                        $this->getFieldName(
                            ConfigFields::DEFAULT_BUTTON_TEXT,
                            $lang,
                        ),
                        __(
                            'Default Placeholder Button Text',
                            $this->textDomain,
                        ),
                    )->set_default_value('Inhalt laden'),
                ],
            );
        }

        $serviceField->add_fields($serviceFieldFields)->set_header_template("
                <% if ( $prettyNameFieldName ) { %>
                    <%- $prettyNameFieldName %>
                <% } else if ( $idFieldName ) { %>
                    <%- $idFieldName %>
                <% } else { %>
                    (( new service ))
                <% } %>");
    }

    protected function getMappedServices($lang): array
    {
        $rawServices = $this->getFieldValue(ConfigFields::SERVICES);
        $services = [];

        $fields = [
            'prettyName' => ConfigFields::SERVICE_PRETTY_NAME,
            'cmpServiceId' => ConfigFields::SERVICE_CMP_SERVICE_ID,
            'privacyPolicySection' =>
                ConfigFields::SERVICE_PRIVACY_POLICY_SECTION,
        ];

        $translatedFields = [
            'titleText' => ConfigFields::SERVICE_TITLE_TEXT,
            'buttonText' => ConfigFields::SERVICE_BUTTON_TEXT,
            'description' => ConfigFields::SERVICE_DESCRIPTION,
        ];

        foreach ($rawServices as $service) {
            $serviceId =
                $service[$this->getFieldName(ConfigFields::SERVICE_ID)];
            $serviceDef = [];

            foreach ($fields as $key => $metaFieldName) {
                $value = $service[$this->getFieldName($metaFieldName)] ?? null;
                if (!empty($value)) {
                    $serviceDef[$key] = $value;
                }
            }

            foreach ($translatedFields as $key => $metaFieldName) {
                $value =
                    $service[$this->getFieldName($metaFieldName, $lang)] ??
                    null;
                if (!empty($value)) {
                    $serviceDef[$key] = $value;
                }
            }

            $services[$serviceId] = $serviceDef;
        }

        return $services;
    }

    protected function getPrivacyPolicyUrl(string $lang = 'en'): string
    {
        $privacyPolicyPageId = (int) get_option('wp_page_for_privacy_policy');

        return get_permalink(
            $this->translator->getPostIdForLanguage(
                $privacyPolicyPageId,
                $lang,
            ),
        );
    }

    /**
     * @return mixed
     */
    protected function getFieldValue(string $id, string $lang = null)
    {
        return carbon_get_theme_option($this->getFieldName($id, $lang));
    }

    protected function getFieldName(string $id, string $lang = null): string
    {
        $name = self::PREF . $id;
        if (!empty($lang)) {
            $name .= '_' . $lang;
        }

        return $name;
    }
}
