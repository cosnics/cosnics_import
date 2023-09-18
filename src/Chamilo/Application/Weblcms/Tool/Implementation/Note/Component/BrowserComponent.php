<?php
namespace Chamilo\Application\Weblcms\Tool\Implementation\Note\Component;

use Chamilo\Application\Weblcms\Tool\Implementation\Note\Manager;

class BrowserComponent extends Manager
{

    public function getAdditionalParameters(array $additionalParameters = []): array
    {
        $additionalParameters[] = self::PARAM_BROWSE_PUBLICATION_TYPE;

        return parent::getAdditionalParameters($additionalParameters);
    }
}
