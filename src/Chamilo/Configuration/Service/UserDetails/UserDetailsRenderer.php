<?php
namespace Chamilo\Configuration\Service\UserDetails;

use Chamilo\Configuration\Form\Viewer;
use Chamilo\Core\User\Architecture\Interfaces\UserDetailsRendererInterface;
use Chamilo\Core\User\Architecture\Traits\UserDetailsRendererTrait;
use Chamilo\Core\User\Manager;
use Chamilo\Core\User\Storage\DataClass\User;
use Chamilo\Libraries\Format\Structure\Glyph\InlineGlyph;
use Chamilo\Libraries\Format\Structure\Glyph\NamespaceIdentGlyph;
use Symfony\Component\Translation\Translator;

/**
 * @package Chamilo\Configuration\Service\UserDetails
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
class UserDetailsRenderer implements UserDetailsRendererInterface
{
    use UserDetailsRendererTrait;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    public function getGlyph(): InlineGlyph
    {
        return new NamespaceIdentGlyph('Chamilo\Configuration', true);
    }

    protected function getViewer(User $user): Viewer
    {
        return new Viewer(
            Manager::CONTEXT, 'account_fields', $user->getId(), null
        );
    }

    /**
     * @throws \ReflectionException
     * @throws \Chamilo\Libraries\Architecture\Exceptions\UserException
     */
    public function hasContentForUser(User $user, User $requestingUser): bool
    {
        return $this->getViewer($user)->get_form_values()->count() > 0;
    }

    public function renderTitle(User $user, User $requestingUser): string
    {
        return $this->getTranslator()->trans('AdditionalUserInformation', [], Manager::CONTEXT);
    }

    public function renderUserDetails(User $user, User $requestingUser): string
    {
        return $this->getViewer($user)->render();
    }
}