<?php

namespace Sillynet\ConsentTools\Action;

use Sillynet\Adretto\Contracts\Translator;
use Sillynet\Adretto\Modules\REST\Action\RestAction;
use Sillynet\Adretto\Modules\REST\RestRoute;
use Sillynet\ConsentTools\Service\ConsentTools;
use WP_REST_Request;

class RegisterRestAction extends RestAction
{
    protected ConsentTools $service;
    protected Translator $translator;

    public function __construct(
        ConsentTools $consentTools,
        Translator $translator
    ) {
        $this->service = $consentTools;
        $this->translator = $translator;
    }

    /**
     * @inheritDoc
     */
    protected function getRoute(): RestRoute
    {
        return RestRoute::create(
            'Consent Management Configuration',
            'consent-management/config',
        )
            ->allowAnyone()
            ->setMethods('GET')
            ->addArgument(
                'lang',
                'The language used by the frontend module',
                'en',
                'string',
                null,
                [$this, 'validateLanguageExists'],
            );
    }

    /**
     * @inheritDoc
     */
    protected function handle(WP_REST_Request $request)
    {
        $language = $request['lang'];

        return $this->service->getConsentToolsConfig($language);
    }

    public function validateLanguageExists($language): bool
    {
        if (!is_string($language)) {
            return false;
        }

        $validLanguages = $this->translator->getAllLanguages();
        $validLanguageSlugs = array_column($validLanguages, 'slug');

        return in_array($language, $validLanguageSlugs);
    }
}
