<?php
namespace Chamilo\Core\Repository\ContentObject\AssessmentMatchNumericQuestion\Integration\Chamilo\Core\Repository\ContentObject\Assessment\Display;

use Chamilo\Core\Repository\ContentObject\Assessment\Display\Component\Viewer\Wizard\Inc\ScoreCalculator;

/**
 *
 * @package repository.lib.complex_display.assessment.component.viewer.wizard.inc.score_calculator
 */
class ScoreCalculator extends ScoreCalculator
{

    public function calculate_score()
    {
        $user_answers = $this->get_answer();
        $user_answer = trim($user_answers[0]);
        
        if (is_null($user_answer) || $user_answer == '')
        {
            return 0;
        }
        
        $question = $this->get_question();
        $tolerance_type = $question->get_tolerance_type();
        
        $max_score = $question->get_best_option()->get_score();
        $options = $question->get_options();
        
        $result = 0;
        foreach ($options as $option)
        {
            if ($option->matches($user_answer, $tolerance_type))
            {
                return $this->make_score_relative($option->get_score(), $max_score);
            }
        }
        
        return $result;
    }
}
