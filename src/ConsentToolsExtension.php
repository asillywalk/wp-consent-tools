<?php

namespace Sillynet\ConsentTools;

use Sillynet\Adretto\Contracts\AdrettoExtension;
use Sillynet\ConsentTools\Action\InitCarbonFieldsAction;
use Sillynet\ConsentTools\Action\RegisterConfigFieldsAction;

class ConsentToolsExtension implements AdrettoExtension
{
    /**
     * @inheritDoc
     */
    public function getServices(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getActions(): array
    {
        return [
            InitCarbonFieldsAction::class,
            RegisterConfigFieldsAction::class,
        ];
    }
}
