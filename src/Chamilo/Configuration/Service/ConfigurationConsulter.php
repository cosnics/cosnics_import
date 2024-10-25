<?php
namespace Chamilo\Configuration\Service;

use Chamilo\Configuration\Interfaces\DataLoaderInterface;

/**
 *
 * @package Chamilo\Configuration\Service
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 */
class ConfigurationConsulter
{
    protected ?array $settings = null;

    public function __construct(protected DataLoaderInterface $cacheableAggregatedDataLoader)
    {

    }

    public function getSettings(): array
    {
        if(is_null($this->settings))
        {
            $this->settings = $this->cacheableAggregatedDataLoader->getData();
            //dump($this->settings);
        }

        return $this->settings;
    }

    public function getSetting(array $keys): mixed
    {
        try
        {
            $variables = $keys;
            $values = $this->getSettings();

            while (count($variables) > 0)
            {
                $key = array_shift($variables);

                if (!array_key_exists($key, $values))
                {
                    throw new \Exception(
                        'The requested variable is not available in an unconfigured environment (' .
                        implode(' > ', $keys) .
                        ')'
                    );
                }
                else
                {
                    $values = $values[$key];
                }
            }

            return $values;
        }
        catch(\Exception)
        {
            return '';
        }
    }

    /*protected function setSetting(array $keys, mixed $value): void
    {
        $variables = $keys;
        $values = $this->getSettings();

        while (count($variables) > 0)
        {
            $key = array_shift($variables);

            if (! isset($values[$key]))
            {
                $values[$key] = null;
                $values = &$values[$key];
            }
            else
            {
                $values = &$values[$key];
            }
        }

        $values = $value;
    }*/

    public function hasSettingsForContext(string $context): bool
    {
        $settings = $this->getSettings();
        return isset($settings[$context]);
    }
}
