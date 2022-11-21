<?php

namespace Sillynet\ConsentTools\Action;

use Sillynet\Adretto\Action\ActionHookAction;
use Sillynet\Adretto\Action\InvokerWordpressHookAction;
use Sillynet\ConsentTools\Service\ConsentTools;

class RegisterConfigFieldsAction extends InvokerWordpressHookAction implements
    ActionHookAction
{
    protected ConsentTools $service;

    /**
     * @inheritDoc
     */
    public static function getWpHookName(): string
    {
        return 'carbon_fields_register_fields';
    }

    public function __construct(ConsentTools $consentTools)
    {
        $this->service = $consentTools;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(...$args)
    {
        $this->service->registerSettingsPage();
    }
}
