<?php

namespace Chamilo\Core\Repository\Form\Type;

use Chamilo\Core\Repository\Service\CategoryService;
use Chamilo\Core\Repository\Storage\DataClass\RepositoryCategory;
use Chamilo\Core\User\Storage\DataClass\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class CopyFormType
 *
 * @package Chamilo\Core\Repository\Form\Type
 * @author Sven Vanpoucke
 */
class CopyFormType extends AbstractType
{
    const TRANSLATION_CONTEXT = 'Chamilo\Core\Repository';

    const ELEMENT_CATEGORY = 'category';
    const OPTION_USER = 'user';

    /**
     * @var \Symfony\Component\Translation\Translator
     */
    protected $translator;

    /**
     * @var CategoryService
     */
    protected $categoryService;

    /**
     * AcceptInviteFormType constructor.
     *
     * @param \Symfony\Component\Translation\Translator $translator
     * @param CategoryService $categoryService
     */
    public function __construct(\Symfony\Component\Translation\Translator $translator, CategoryService $categoryService)
    {
        $this->translator = $translator;
        $this->categoryService = $categoryService;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $categories = $this->categoryService->getCategoryTreeForForm($this->getUserFromOptions($options));

        $builder->add(
            self::ELEMENT_CATEGORY, ChoiceType::class,
            [
                'label' => $this->translator->trans('Category', [], self::TRANSLATION_CONTEXT),
                'choices' => $categories
            ]
        );
    }

    /**
     * @param array $options
     *
     * @return User
     */
    protected function getUserFromOptions(array $options)
    {
        $user = $options[self::OPTION_USER];
        if (!$user instanceof User)
        {
            throw new \RuntimeException('The given user is not a valid User object');
        }

        return $user;
    }

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                self::OPTION_USER => null
            ]
        );
    }


}