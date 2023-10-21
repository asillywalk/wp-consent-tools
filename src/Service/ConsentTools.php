<?php

namespace Sillynet\ConsentTools\Service;

use Carbon_Fields\Container;
use Carbon_Fields\Field;
use Psr\Container\ContainerInterface;
use Sillynet\Adretto\Contracts\Translator;
use Sillynet\Adretto\Theme;
use Sillynet\ConsentTools\ConfigFields;

use function carbon_get_theme_option;
use function __;

const consentToolsSettingsSchema = [
    'additionalServices' => 'Array<string>',
    'checkboxLabel' => 'TranslatableSetting',
    'checkboxProviderName' => 'TranslatableSetting',
    'clickOnConsent' => 'boolean',
    'cmpServiceId' => 'string | null',
    'defaultLoadAll' => 'boolean',
    'modalOpenerButton' => 'boolean',
    'modalOpenerButtonText' => 'TranslatableSetting',
    'permanentConsentType' => 'PermanentConsentType',
    'privacyPolicySection' => 'string',
    'placeholderBody' => 'TranslatableSetting',
    'reloadOnConsent' => 'boolean',
    'serviceDescription' => 'TranslatableSetting',
    'servicePrettyName' => 'TranslatableSetting',
    'titleText' => 'TranslatableSetting',
    //
    'autoLoadOnButtonClick' => 'boolean',
    'privacyPolicyUrl' => 'string',
    'categories' =>
        'array<string, array{label: TranslatableSetting, color?: string}>',
    'tiers' => 'Record<Tier, TranslatableSetting>',
];

class ConsentTools
{
    /**
     * Prefix for theme option fields
     */
    protected const PREF = 'sn_consent_management_';
    protected const PERMANENT_CONSENT_OPTIONS = [
        'none' => 'None',
        'checkbox' => 'Checkbox',
        'button' => 'Button',
    ];

    protected string $textDomain = 'sillynet';
    protected Translator $translator;
    protected Theme $theme;

    public function __construct(
        Translator $translator,
        ContainerInterface $container
    ) {
        $this->translator = $translator;
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
                'clickOnConsent' => $this->getFieldValue(
                    ConfigFields::DEFAULT_CLICK_ON_CONSENT,
                ),
                'defaultLoadAll' => $this->getFieldValue(
                    ConfigFields::DEFAULT_LOAD_ALL,
                ),
                'modalOpenerButton' => $this->getFieldValue(
                    ConfigFields::DEFAULT_MODAL_OPENER_BUTTON,
                ),
                'permanentConsentType' => $this->getFieldValue(
                    ConfigFields::DEFAULT_PERMANENT_CONSENT_TYPE,
                ),
                'privacyPolicyUrl' => $this->getPrivacyPolicyUrl($lang),
            ],
            'types' => $this->getMappedServices($lang),
            'dict' => [
                'ph_PermanentConsentLabel' => [
                    $lang => $this->getFieldValue(
                        ConfigFields::DEFAULT_CHECKBOX_LABEL,
                        $lang,
                    ),
                ],
                'ph_ModalOpenerButtonText' => [
                    $lang => $this->getFieldValue(
                        ConfigFields::DEFAULT_MODAL_OPENER_BUTTON_TEXT,
                        $lang,
                    ),
                ],
                'ph_TitleText' => [
                    $lang => $this->getFieldValue(
                        ConfigFields::DEFAULT_TITLE_TEXT,
                        $lang,
                    ),
                ],
                'ph_Body' => [
                    $lang => $this->getFieldValue(
                        ConfigFields::DEFAULT_PLACEHOLDER_BODY,
                        $lang,
                    ),
                ],
                'ph_ButtonText' => [
                    $lang => $this->getFieldValue(
                        ConfigFields::DEFAULT_BUTTON_TEXT,
                        $lang,
                    ),
                ],
            ],
        ];
    }

    public function registerSettingsPage()
    {
        $idFieldName = $this->getFieldName(ConfigFields::SERVICE_ID);
        $prettyNameFieldName = $this->getFieldName(
            ConfigFields::SERVICE_PRETTY_NAME,
            'en',
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
                $this->getFieldName(ConfigFields::SERVICE_CMP_SERVICE_ID),
                __('CMP Service ID', $this->textDomain),
            )->set_help_text(
                'Set this value if your CMP uses a different ID for this service/vendor than the above.',
            ),
            Field::make_select(
                $this->getFieldName(ConfigFields::SERVICE_TIER),
                __('Service Consent Tier', $this->textDomain),
            )
                ->set_options([
                    '0' => 'Red (necessary) – 0',
                    '1' => 'Amber (anonymous & UX) – 1',
                    '2' => 'Green (marketing & analytics) – 2',
                ])
                ->set_default_value('2'),
            Field::make_text(
                $this->getFieldName(ConfigFields::SERVICE_CATEGORY),
                __('Category', $this->textDomain),
            ),
            Field::make_text(
                $this->getFieldName(
                    ConfigFields::SERVICE_PRIVACY_POLICY_SECTION,
                ),
                __('Privacy Policy Section', $this->textDomain),
            )->set_help_text(
                'If you want the placeholder templates to link to a specific section inside the privacy policy page, enter the anchor element\'s ID here (without the leading hash "#" character).',
            ),
            Field::make_checkbox(
                $this->getFieldName(ConfigFields::SERVICE_CLICK_ON_CONSENT),
                __(
                    'Simulate button click once consent is given',
                    $this->textDomain,
                ),
            )->set_default_value(false),
            Field::make_checkbox(
                $this->getFieldName(ConfigFields::SERVICE_DEFAULT_LOAD_ALL),
                __(
                    'Imply permanent consent intention (only enable for checkbox permanent consent type)',
                    $this->textDomain,
                ),
            )->set_default_value(true),
            Field::make_checkbox(
                $this->getFieldName(ConfigFields::SERVICE_MODAL_OPENER_BUTTON),
                __(
                    'Show a button for opening the consent settings modal',
                    $this->textDomain,
                ),
            )->set_default_value(true),
            Field::make_select(
                $this->getFieldName(
                    ConfigFields::SERVICE_PERMANENT_CONSENT_TYPE,
                ),
                __('Type of permanent consent option', $this->textDomain),
            )
                ->set_options(self::PERMANENT_CONSENT_OPTIONS)
                ->set_default_value('none'),
            Field::make_checkbox(
                $this->getFieldName(ConfigFields::SERVICE_RELOAD_ON_CONSENT),
                __(
                    'Force reload page when consent to this service is given',
                    $this->textDomain,
                ),
            )->set_default_value(false),
        ];

        $container->add_tab(__('Services', $this->textDomain), [$serviceField]);
        $container->add_tab(__('Defaults (global)', $this->textDomain), [
            Field::make_checkbox(
                $this->getFieldName(ConfigFields::DEFAULT_CLICK_ON_CONSENT),
                __(
                    'Simulate a click after consent of given by default',
                    $this->textDomain,
                ),
            )->set_default_value(false),
            Field::make_checkbox(
                $this->getFieldName(ConfigFields::DEFAULT_LOAD_ALL),
                __(
                    'Imply permanent consent intention by default (deactivate when using buttons)',
                    $this->textDomain,
                ),
            )->set_default_value(true),
            Field::make_checkbox(
                $this->getFieldName(ConfigFields::DEFAULT_MODAL_OPENER_BUTTON),
                __(
                    'Display a modal opener button ("More info") for each service by default',
                    $this->textDomain,
                ),
            )->set_default_value(true),
            Field::make_select(
                $this->getFieldName(
                    ConfigFields::DEFAULT_PERMANENT_CONSENT_TYPE,
                ),
                __('Type of permanent consent option', $this->textDomain),
            )
                ->set_options(self::PERMANENT_CONSENT_OPTIONS)
                ->set_default_value('none'),
        ]);

        foreach ($this->translator->getAllLanguages() as $language) {
            $lang = $language['slug'];

            // translated fields
            $serviceFieldFields = array_merge($serviceFieldFields, [
                Field::make_separator(
                    'sep-1-' . $lang,
                    __('Service l11n', $this->textDomain) .
                        ' ' .
                        $language['name'],
                ),
                Field::make_text(
                    $this->getFieldName(
                        ConfigFields::SERVICE_PRETTY_NAME,
                        $lang,
                    ),
                    $this->labelWithLanguageSuffix(
                        __('Pretty Name', $this->textDomain),
                        $lang,
                    ),
                ),
                Field::make_textarea(
                    $this->getFieldName(
                        ConfigFields::SERVICE_DESCRIPTION,
                        $lang,
                    ),
                    $this->labelWithLanguageSuffix(
                        __(
                            'Service Description (in settings)',
                            $this->textDomain,
                        ),
                        $lang,
                    ),
                )->set_rows(3),
                Field::make_separator(
                    'sep-2-' . $lang,
                    __('Placeholder', $this->textDomain) .
                        ' ' .
                        $language['name'],
                ),

                Field::make_textarea(
                    $this->getFieldName(
                        ConfigFields::SERVICE_PLACEHOLDER_BODY,
                        $lang,
                    ),
                    $this->labelWithLanguageSuffix(
                        __(
                            'Service Placeholder Body Content',
                            $this->textDomain,
                        ),
                        $lang,
                    ),
                ),
                Field::make_text(
                    $this->getFieldName(
                        ConfigFields::SERVICE_TITLE_TEXT,
                        $lang,
                    ),
                    $this->labelWithLanguageSuffix(
                        __('Placeholder Heading', $this->textDomain),
                        $lang,
                    ),
                ),
                Field::make_text(
                    $this->getFieldName(
                        ConfigFields::SERVICE_BUTTON_TEXT,
                        $lang,
                    ),
                    $this->labelWithLanguageSuffix(
                        __('Placeholder Button Text', $this->textDomain),
                        $lang,
                    ),
                ),
                Field::make_text(
                    $this->getFieldName(
                        ConfigFields::SERVICE_PERMANENT_CONSENT_LABEL,
                        $lang,
                    ),
                    $this->labelWithLanguageSuffix(
                        __(
                            'Label for the permanent consent type input (checkbox/button)',
                            $this->textDomain,
                        ),
                        $lang,
                    ),
                )
                    ->set_default_value('Allow all')
                    ->set_conditional_logic([
                        [
                            'field' => $this->getFieldName(
                                ConfigFields::SERVICE_PERMANENT_CONSENT_TYPE,
                            ),
                            'value' => ['none'],
                            'compare' => 'NOT IN',
                        ],
                    ]),
                Field::make_text(
                    $this->getFieldName(
                        ConfigFields::SERVICE_CHECKBOX_PROVIDER_NAME,
                        $lang,
                    ),
                    $this->labelWithLanguageSuffix(
                        __(
                            'Service name within placeholder checkbox label (permanent consent)',
                            $this->textDomain,
                        ),
                        $lang,
                    ),
                ),
                Field::make_text(
                    $this->getFieldName(
                        ConfigFields::SERVICE_MODAL_OPENER_BUTTON_TEXT,
                        $lang,
                    ),
                    $this->labelWithLanguageSuffix(
                        __('Modal opener button text', $this->textDomain),
                        $lang,
                    ),
                ),
            ]);

            $container->add_tab(
                __('Defaults', $this->textDomain) . ' (' . $lang . ')',
                [
                    Field::make_textarea(
                        $this->getFieldName(
                            ConfigFields::DEFAULT_PLACEHOLDER_BODY,
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
                            ConfigFields::DEFAULT_CHECKBOX_LABEL,
                            $lang,
                        ),
                        __(
                            'Default Checkbox Label (permanent consent)',
                            $this->textDomain,
                        ),
                    )->set_default_value('Erlauben & merken'),
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
                    Field::make_text(
                        $this->getFieldName(
                            ConfigFields::DEFAULT_MODAL_OPENER_BUTTON_TEXT,
                            $lang,
                        ),
                        __(
                            'Modal opener button: default text',
                            $this->textDomain,
                        ),
                    )->set_default_value('Mehr Informationen'),
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
            'clickOnConsent' => ConfigFields::SERVICE_CLICK_ON_CONSENT,
            'cmpServiceId' => ConfigFields::SERVICE_CMP_SERVICE_ID,
            'defaultLoadAll' => ConfigFields::SERVICE_DEFAULT_LOAD_ALL,
            'modalOpenerButton' => ConfigFields::SERVICE_MODAL_OPENER_BUTTON,
            'permanentConsentType' =>
                ConfigFields::SERVICE_PERMANENT_CONSENT_TYPE,
            'privacyPolicySection' =>
                ConfigFields::SERVICE_PRIVACY_POLICY_SECTION,
            'reloadOnConsent' => ConfigFields::SERVICE_RELOAD_ON_CONSENT,
            'tier' => ConfigFields::SERVICE_TIER,
            'category' => ConfigFields::SERVICE_CATEGORY,
        ];

        $translatedFields = [
            'titleText' => ConfigFields::SERVICE_TITLE_TEXT,
            'ph_ButtonText' => ConfigFields::SERVICE_BUTTON_TEXT,
            'ph_PermanentConsentLabel' =>
                ConfigFields::SERVICE_PERMANENT_CONSENT_LABEL,
            'checkboxProviderName' =>
                ConfigFields::SERVICE_CHECKBOX_PROVIDER_NAME,
            'serviceDescription' => ConfigFields::SERVICE_DESCRIPTION,
            'ph_ModalOpenerButtonText' =>
                ConfigFields::SERVICE_MODAL_OPENER_BUTTON_TEXT,
            'ph_Body' => ConfigFields::SERVICE_PLACEHOLDER_BODY,
            'servicePrettyName' => ConfigFields::SERVICE_PRETTY_NAME,
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
                    $serviceDef[$key] = [
                        $lang => $value,
                    ];
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

    protected function labelWithLanguageSuffix($string, $lang): string
    {
        return $string . ' (' . $lang . ')';
    }
}
