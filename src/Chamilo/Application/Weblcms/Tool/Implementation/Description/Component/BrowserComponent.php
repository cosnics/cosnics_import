<?php
namespace Chamilo\Application\Weblcms\Tool\Implementation\Description\Component;

use Chamilo\Application\Weblcms\Tool\Implementation\Description\Manager;

class BrowserComponent extends Manager
{

    /*
     * Inherited.
     */

    public function getAdditionalParameters(array $additionalParameters = []): array
    {
        $additionalParameters[] = self::PARAM_BROWSE_PUBLICATION_TYPE;

        return parent::getAdditionalParameters($additionalParameters);
    }

    public function get_publication_count()
    {
        return count($this->publications);
    }
}
