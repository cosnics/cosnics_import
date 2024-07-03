<?php
namespace Chamilo\Core\Menu\Architecture\Interfaces;

use Chamilo\Core\Menu\Storage\DataClass\Item;
use Chamilo\Libraries\Storage\Query\OrderBy;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @package Chamilo\Core\Menu\Architecture\Interfaces
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
interface ItemServiceInterface
{
    public function countItemsByParentIdentifier(string $parentIdentifier): int;

    /**
     * @throws \Chamilo\Libraries\Storage\Architecture\Exceptions\DisplayOrderException
     * @throws \Exception
     */
    public function createItem(Item $item): bool;

    /**
     * @param string[][] $values
     *
     * @throws \Chamilo\Libraries\Storage\Architecture\Exceptions\DisplayOrderException
     * @throws \Exception
     */
    public function createItemForTypeFromValues(string $itemType, array $values): ?Item;

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Exception
     */
    public function deleteItem(Item $item): bool;

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function deleteItemChildren(Item $item): bool;

    public function doesItemHaveChildren(Item $item): bool;

    public function findItemByIdentifier(string $identifier): ?Item;

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection<\Chamilo\Core\Menu\Storage\DataClass\Item>
     */
    public function findItems(): ArrayCollection;

    /**
     * @param string[] $identifiers
     *
     * @return \Doctrine\Common\Collections\ArrayCollection<\Chamilo\Core\Menu\Storage\DataClass\Item>
     */
    public function findItemsByIdentifiers(array $identifiers): ArrayCollection;

    /**
     * @param string $parentIdentifier
     * @param ?int $count
     * @param ?int $offset
     * @param \Chamilo\Libraries\Storage\Query\OrderBy $orderBy
     *
     * @return \Doctrine\Common\Collections\ArrayCollection<\Chamilo\Core\Menu\Storage\DataClass\Item>
     */
    public function findItemsByParentIdentifier(
        string $parentIdentifier, ?int $count = null, ?int $offset = null, OrderBy $orderBy = new OrderBy()
    ): ArrayCollection;

    /**
     * @return \Chamilo\Core\Menu\Storage\DataClass\Item[][]
     */
    public function findItemsGroupedByParentIdentifier(): array;

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection<\Chamilo\Core\Menu\Storage\DataClass\Item>
     */
    public function findRootCategoryItems(): ArrayCollection;

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection<\Chamilo\Core\Menu\Storage\DataClass\Item>
     */
    public function findRootItems(): ArrayCollection;

    public function getNextItemSortValueByParentIdentifier(string $parentIdentifier): int;

    /**
     * @throws \Chamilo\Libraries\Storage\Architecture\Exceptions\DisplayOrderException
     */
    public function moveItemInDirection(Item $item, int $moveDirection): bool;

    /**
     * @param string[][] $values
     *
     * @throws \Chamilo\Libraries\Storage\Architecture\Exceptions\DisplayOrderException
     */
    public function saveItemFromValues(Item $item, array $values): bool;

    /**
     * @throws \Chamilo\Libraries\Storage\Architecture\Exceptions\DisplayOrderException
     */
    public function updateItem(Item $item): bool;
}