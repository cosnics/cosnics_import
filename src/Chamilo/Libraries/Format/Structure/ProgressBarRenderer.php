<?php
namespace Chamilo\Libraries\Format\Structure;

use InvalidArgumentException;

/**
 * Renders a progressbar
 *
 * @package Chamilo\Libraries\Format\Structure
 * @author Sven Vanpoucke - Hogeschool Gent
 */
class ProgressBarRenderer
{
    public const MODE_DANGER = 'danger';
    public const MODE_DEFAULT = 'default';
    public const MODE_INFO = 'info';
    public const MODE_SUCCESS = 'success';
    public const MODE_WARNING = 'warning';

    public function render(int $progress, string $mode = self::MODE_DEFAULT, int $maxWidth = 150, bool $striped = false
    ): string
    {
        $this->validateProgress($progress);
        $this->validateMode($mode);

        $maxWidth = is_integer($maxWidth) && $maxWidth > 0 ? $maxWidth . 'px' : '100%';

        $contextualClass = $mode == self::MODE_DEFAULT ? '' : 'progress-bar-' . $mode;

        if ($striped)
        {
            $contextualClass .= ' progress-bar-striped';
        }

        $html = [];

        $html[] = '<div class="progress" style="margin-bottom: 0; max-width: ' . $maxWidth . ';">';
        $html[] = '<div class="progress-bar ' . $contextualClass . '" role="progressbar" aria-valuenow="' . $progress;
        $html[] = '" aria-valuemin="0" aria-valuemax="100" style="min-width: 2em; width: ' . $progress . '%;">';
        $html[] = $progress . '%';
        $html[] = '</div>';
        $html[] = '</div>';

        return implode(PHP_EOL, $html);
    }

    /**
     * @return string[]
     */
    protected function getAllowedModes(): array
    {
        return [self::MODE_DEFAULT, self::MODE_SUCCESS, self::MODE_INFO, self::MODE_WARNING, self::MODE_DANGER];
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function validateMode(string $mode = self::MODE_DEFAULT)
    {
        if (!in_array($mode, $this->getAllowedModes()))
        {
            throw new InvalidArgumentException(
                sprintf(
                    'The given mode must be a valid string and must be one of (%s)',
                    implode(', ', $this->getAllowedModes())
                )
            );
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function validateProgress(int $progress)
    {
        if ($progress < 0 || $progress > 100)
        {
            throw new InvalidArgumentException(
                'The given progress must be a valid integer and must be between 0 and 100'
            );
        }
    }
}