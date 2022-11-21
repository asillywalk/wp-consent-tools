<?php

namespace Sillynet\ConsentTools\Action;

use Carbon_Fields\Carbon_Fields;
use Sillynet\Adretto\Action\ActionHookAction;
use Sillynet\Adretto\Action\InvokerWordpressHookAction;

class InitCarbonFieldsAction extends InvokerWordpressHookAction implements
    ActionHookAction
{
    /**
     * @inheritDoc
     */
    public function __invoke(...$args)
    {
        Carbon_Fields::boot();
    }

    /**
     * @inheritDoc
     */
    public static function getWpHookName(): string
    {
        return 'after_setup_theme';
    }
}
