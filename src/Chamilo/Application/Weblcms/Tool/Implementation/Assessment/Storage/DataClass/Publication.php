<?php
namespace Chamilo\Application\Weblcms\Tool\Implementation\Assessment\Storage\DataClass;

use Chamilo\Application\Weblcms\Tool\Implementation\Assessment\Manager;
use Chamilo\Core\Repository\ContentObject\Assessment\Display\Configuration;
use Chamilo\Libraries\Storage\DataClass\DataClass;

/**
 * @package application\weblcms\tool\implementation\assessment
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author  Magali Gillard <magali.gillard@ehb.be>
 * @author  Eduard Vossen <eduard.vossen@ehb.be>
 */
class Publication extends DataClass
{
    public const CONTEXT = Manager::CONTEXT;

    public const PROPERTY_ALLOW_HINTS = 'allow_hints';
    public const PROPERTY_FEEDBACK_LOCATION = 'feedback_location';
    public const PROPERTY_PUBLICATION_ID = 'publication_id';
    public const PROPERTY_SHOW_ANSWER_FEEDBACK = 'show_answer_feedback';
    public const PROPERTY_SHOW_CORRECTION = 'show_correction';
    public const PROPERTY_SHOW_SCORE = 'show_score';
    public const PROPERTY_SHOW_SOLUTION = 'show_solution';

    /**
     * Get the default properties
     *
     * @return array The property names.
     */
    public static function getDefaultPropertyNames(array $extendedPropertyNames = []): array
    {
        return parent::getDefaultPropertyNames(
            [
                self::PROPERTY_PUBLICATION_ID,
                self::PROPERTY_ALLOW_HINTS,
                self::PROPERTY_SHOW_SCORE,
                self::PROPERTY_SHOW_CORRECTION,
                self::PROPERTY_SHOW_SOLUTION,
                self::PROPERTY_SHOW_ANSWER_FEEDBACK,
                self::PROPERTY_FEEDBACK_LOCATION
            ]
        );
    }

    /**
     * @return string
     */
    public static function getStorageUnitName(): string
    {
        return 'weblcms_assessment_publication';
    }

    /**
     * @return bool
     */
    public function get_allow_hints()
    {
        return $this->getDefaultProperty(self::PROPERTY_ALLOW_HINTS);
    }

    /**
     * @return \core\repository\content_object\assessment\Configuration
     */
    public function get_configuration()
    {
        return new Configuration(
            $this->get_allow_hints(), $this->get_show_score(), $this->get_show_correction(), $this->get_show_solution(),
            $this->get_show_answer_feedback(), $this->get_feedback_location()
        );
    }

    /**
     * @return int
     */
    public function get_feedback_location()
    {
        return $this->getDefaultProperty(self::PROPERTY_FEEDBACK_LOCATION);
    }

    public function get_publication_id()
    {
        return $this->getDefaultProperty(self::PROPERTY_PUBLICATION_ID);
    }

    /**
     * @return int
     */
    public function get_show_answer_feedback()
    {
        return $this->getDefaultProperty(self::PROPERTY_SHOW_ANSWER_FEEDBACK);
    }

    /**
     * @return bool
     */
    public function get_show_correction()
    {
        return $this->getDefaultProperty(self::PROPERTY_SHOW_CORRECTION);
    }

    /**
     * @return bool
     */
    public function get_show_score()
    {
        return $this->getDefaultProperty(self::PROPERTY_SHOW_SCORE);
    }

    /**
     * @return bool
     */
    public function get_show_solution()
    {
        return $this->getDefaultProperty(self::PROPERTY_SHOW_SOLUTION);
    }

    /**
     * @param bool $allow_hints
     */
    public function set_allow_hints($allow_hints)
    {
        $this->setDefaultProperty(self::PROPERTY_ALLOW_HINTS, $allow_hints);
    }

    /**
     * @param int $feedback_location
     */
    public function set_feedback_location($feedback_location)
    {
        $this->setDefaultProperty(self::PROPERTY_FEEDBACK_LOCATION, $feedback_location);
    }

    public function set_publication_id($publication_id)
    {
        $this->setDefaultProperty(self::PROPERTY_PUBLICATION_ID, $publication_id);
    }

    /**
     * @param int $show_answer_feedback
     */
    public function set_show_answer_feedback($show_answer_feedback)
    {
        $this->setDefaultProperty(self::PROPERTY_SHOW_ANSWER_FEEDBACK, $show_answer_feedback);
    }

    /**
     * @param bool $show_correction
     */
    public function set_show_correction($show_correction)
    {
        $this->setDefaultProperty(self::PROPERTY_SHOW_CORRECTION, $show_correction);
    }

    /**
     * @param bool $show_score
     */
    public function set_show_score($show_score)
    {
        $this->setDefaultProperty(self::PROPERTY_SHOW_SCORE, $show_score);
    }

    /**
     * @param bool $show_solution
     */
    public function set_show_solution($show_solution)
    {
        $this->setDefaultProperty(self::PROPERTY_SHOW_SOLUTION, $show_solution);
    }
}
