<?php

namespace Chamilo\Core\Repository\ContentObject\Rubric\Ajax\Model;

use Chamilo\Core\Repository\ContentObject\Rubric\Storage\Entity\CriteriumNode;
use Chamilo\Core\Repository\ContentObject\Rubric\Storage\Entity\Level;
use Chamilo\Core\Repository\ContentObject\Rubric\Storage\Entity\RubricData;
use http\Exception\InvalidArgumentException;
use JMS\Serializer\Annotation\Type;

/**
 * @package Chamilo\Core\Repository\ContentObject\Rubric\Ajax
 *
 * @author Sven Vanpoucke - Hogeschool Gent
 */
class LevelJSONModel
{
    /**
     * @var int
     *
     * @Type("integer")
     */
    protected $id;

    /**
     * @var string
     *
     * @Type("string")
     */
    protected $title;

    /**
     * @var string
     *
     * @Type("string")
     */
    protected $description;

    /**
     * @var int
     *
     * @Type("integer")
     */
    protected $score;

    /**
     * @var bool
     *
     * @Type("bool")
     */
    protected $isDefault = false;

    /**
     * @var int
     *
     * @Type("integer")
     */
    protected $criteriumId;

    /**
     * LevelJSONModel constructor.
     *
     * @param int $id
     * @param string $title
     * @param int $score
     * @param bool $isDefault
     * @param string|null $description
     * @param int|null $criteriumId
     */
    public function __construct(int $id, string $title, int $score, bool $isDefault, string $description = null, int $criteriumId = null)
    {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->score = $score;
        $this->isDefault = $isDefault;
        $this->criteriumId = $criteriumId;
    }

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return int
     */
    public function getScore(): ?int
    {
        return $this->score;
    }

    /**
     * @return bool
     */
    public function isDefault(): ?bool
    {
        return $this->isDefault;
    }

    /**
     * @return int|null
     */
    public function getCriteriumId(): ?int
    {
        return $this->criteriumId;
    }

    /**
     * @param RubricData $rubricData
     *
     * @return Level
     *
     * @throws \Chamilo\Libraries\Architecture\Exceptions\ObjectNotExistException
     */
    public function toLevel(RubricData $rubricData)
    {
        $criterium = !empty($this->criteriumId) ? $rubricData->getCriteriumById($this->criteriumId) : null;
        $level = new Level($rubricData, $criterium);
        $this->updateLevel($level);

        return $level;
    }

    /**
     * @param Level $level
     *
     * @return Level
     */
    public function updateLevel(Level $level)
    {
        $level->setId($this->id);
        $level->setTitle($this->title);
        $level->setDescription($this->description);
        $level->setScore($this->score);
        $level->setIsDefault($this->isDefault);

        return $level;
    }

    /**
     * @param Level $level
     *
     * @return LevelJSONModel
     */
    public static function fromLevel(Level $level)
    {
        return new self(
            $level->getId(), $level->getTitle(), $level->getScore(), $level->isDefault(), $level->getDescription(), $level->getCriteriumId()
        );
    }
}
