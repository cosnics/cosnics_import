<?php
namespace Chamilo\Core\Menu\Service;

use Chamilo\Core\Menu\Architecture\Interfaces\ItemServiceInterface;
use Chamilo\Core\Menu\Storage\DataClass\Item;
use Chamilo\Libraries\Cache\Interfaces\CacheDataPreLoaderInterface;
use Chamilo\Libraries\Cache\Traits\SingleCacheAdapterHandlerTrait;
use Chamilo\Libraries\Storage\Query\OrderBy;
use Chamilo\Libraries\Storage\Service\PropertyMapper;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Exception\CacheException;

/**
 * @package Chamilo\Core\Menu\Service
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
class CachedItemService implements CacheDataPreLoaderInterface, ItemServiceInterface
{
    use SingleCacheAdapterHandlerTrait;

    public const KEY_ITEMS = 'items';

    protected RightsCacheService $rightsCacheService;

    private ItemService $itemService;

    private PropertyMapper $propertyMapper;

    public function __construct(
        ItemService $itemService, AdapterInterface $cacheAdapter, PropertyMapper $propertyMapper,
        RightsCacheService $rightsCacheService
    )
    {
        $this->itemService = $itemService;
        $this->cacheAdapter = $cacheAdapter;
        $this->propertyMapper = $propertyMapper;
        $this->rightsCacheService = $rightsCacheService;
    }

    /**
     * @return \Chamilo\Core\Menu\Storage\DataClass\Item[][]
     */
    protected function __findItemsGroupedByParentIdentifier(): array
    {
        return $this->getItemService()->findItemsGroupedByParentIdentifier();
    }

    public function countItemsByParentIdentifier(string $parentIdentifier): int
    {
        return $this->getItemService()->countItemsByParentIdentifier($parentIdentifier);
    }

    public function createItem(Item $item): bool
    {
        if (!$this->getItemService()->createItem($item))
        {
            return false;
        }

        if (!$this->getRightsCacheService()->clear())
        {
            return false;
        }

        return $this->clearCacheDataForKeyParts([__CLASS__, self::KEY_ITEMS]);
    }

    public function createItemForTypeFromValues(string $itemType, array $values): ?Item
    {
        $item = $this->getItemService()->createItemForTypeFromValues($itemType, $values);

        if (!$item)
        {
            return null;
        }

        if (!$this->getRightsCacheService()->clear())
        {
            return null;
        }

        if (!$this->clearCacheDataForKeyParts([__CLASS__, self::KEY_ITEMS]))
        {
            return null;
        }

        return $item;
    }

    public function deleteItem(Item $item): bool
    {
        if (!$this->getItemService()->deleteItem($item))
        {
            return false;
        }

        if (!$this->getRightsCacheService()->clear())
        {
            return false;
        }

        return $this->clearCacheDataForKeyParts([__CLASS__, self::KEY_ITEMS]);
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Symfony\Component\Cache\Exception\CacheException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function deleteItemChildren(Item $item): bool
    {
        if (!$this->getItemService()->deleteItemChildren($item))
        {
            return false;
        }

        if (!$this->getRightsCacheService()->clear())
        {
            return false;
        }

        return $this->clearCacheDataForKeyParts([__CLASS__, self::KEY_ITEMS]);
    }

    public function doesItemHaveChildren(Item $item): bool
    {
        $groupedItems = $this->findItemsGroupedByParentIdentifier();

        return array_key_exists($item->getId(), $groupedItems);
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection<\Chamilo\Core\Menu\Storage\DataClass\Item>
     */
    public function findApplicationItems(): ArrayCollection
    {
        return $this->getItemService()->findApplicationItems();
    }

    public function findItemByIdentifier(string $identifier): ?Item
    {
        return $this->getItemService()->findItemByIdentifier($identifier);
    }

    public function findItems(): ArrayCollection
    {
        return $this->getItemService()->findItems();
    }

    public function findItemsByIdentifiers(array $identifiers): ArrayCollection
    {
        return $this->getItemService()->findItemsByIdentifiers($identifiers);
    }

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
    ): ArrayCollection
    {
        $groupedItems = $this->findItemsGroupedByParentIdentifier();
        $parentKeyExists = array_key_exists($parentIdentifier, $groupedItems);
        $parentIdentifierItems = $parentKeyExists ? $groupedItems[$parentIdentifier] : [];

        return new ArrayCollection($parentIdentifierItems);
    }

    /**
     * @return \Chamilo\Core\Menu\Storage\DataClass\Item[][]
     */
    public function findItemsGroupedByParentIdentifier(): array
    {
        try
        {
            return $this->loadCacheDataForKeyParts([__CLASS__, self::KEY_ITEMS],
                [$this, '__findItemsGroupedByParentIdentifier']);
        }
        catch (CacheException)
        {
            return [];
        }
    }

    public function findRootCategoryItems(): ArrayCollection
    {
        return $this->getItemService()->findRootCategoryItems();
    }

    public function findRootItems(): ArrayCollection
    {
        return $this->findItemsByParentIdentifier('0');
    }

    public function getItemService(): ItemService
    {
        return $this->itemService;
    }

    public function getNextItemSortValueByParentIdentifier(string $parentIdentifier): int
    {
        return $this->getItemService()->getNextItemSortValueByParentIdentifier($parentIdentifier);
    }

    public function getPropertyMapper(): PropertyMapper
    {
        return $this->propertyMapper;
    }

    public function getRightsCacheService(): RightsCacheService
    {
        return $this->rightsCacheService;
    }

    /**
     * @throws \Symfony\Component\Cache\Exception\CacheException
     * @throws \Chamilo\Libraries\Storage\Architecture\Exceptions\DisplayOrderException
     */
    public function moveItemInDirection(Item $item, int $moveDirection): bool
    {
        if (!$this->getItemService()->moveItemInDirection($item, $moveDirection))
        {
            return false;
        }

        return $this->clearCacheDataForKeyParts([__CLASS__, self::KEY_ITEMS]);
    }

    public function preLoadCacheData(): array
    {
        return $this->findItemsGroupedByParentIdentifier();
    }

    /**
     * @throws \Symfony\Component\Cache\Exception\CacheException
     * @throws \Chamilo\Libraries\Storage\Architecture\Exceptions\DisplayOrderException
     */
    public function saveItemFromValues(Item $item, array $values): bool
    {
        if (!$this->getItemService()->saveItemFromValues($item, $values))
        {
            return false;
        }

        if (!$this->getRightsCacheService()->clear())
        {
            return false;
        }

        return $this->clearCacheDataForKeyParts([__CLASS__, self::KEY_ITEMS]);
    }

    /**
     * @throws \Symfony\Component\Cache\Exception\CacheException
     * @throws \Chamilo\Libraries\Storage\Architecture\Exceptions\DisplayOrderException
     */
    public function updateItem(Item $item): bool
    {
        if (!$this->getItemService() - $this->updateItem($item))
        {
            return false;
        }

        if (!$this->getRightsCacheService()->clear())
        {
            return false;
        }

        return $this->clearCacheDataForKeyParts([__CLASS__, self::KEY_ITEMS]);
    }
}