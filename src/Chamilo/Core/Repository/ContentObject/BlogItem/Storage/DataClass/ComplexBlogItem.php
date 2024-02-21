<?php
namespace Chamilo\Core\Repository\ContentObject\BlogItem\Storage\DataClass;

use Chamilo\Core\Repository\Storage\DataClass\ComplexContentObjectItem;
use Chamilo\Libraries\Storage\DataClass\Interfaces\CompositeDataClassVirtualExtensionInterface;

/**
 * @package repository.lib.content_object.blog_item
 * @author  Hans De Bisschop
 * @author  Dieter De Neef
 */
class ComplexBlogItem extends ComplexContentObjectItem implements CompositeDataClassVirtualExtensionInterface
{
    public const CONTEXT = BlogItem::CONTEXT;
}
