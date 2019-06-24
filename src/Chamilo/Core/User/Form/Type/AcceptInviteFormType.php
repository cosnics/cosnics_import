<?php

namespace Chamilo\Core\User\Form;

use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @package Chamilo\Core\User\Form
 * @author Sven Vanpoucke - Hogeschool Gent
 */
class AcceptInviteFormType extends \Symfony\Component\Form\AbstractType
{
    const ELEMENT_FIRST_NAME = 'first_name';
    const ELEMENT_LAST_NAME = 'last_name';
    const ELEMENT_EMAIL = 'email';
    const ELEMENT_PASSWORD = 'password';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(self::ELEMENT_FIRST_NAME, TextType::class);
        $builder->add(self::ELEMENT_LAST_NAME, TextType::class);
        $builder->add(self::ELEMENT_EMAIL, TextType::class);
        $builder->add(self::ELEMENT_PASSWORD, RepeatedType::class, ['type' => PasswordType::class]);
    }
}