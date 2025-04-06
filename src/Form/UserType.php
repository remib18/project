<?php

namespace App\Form;

use App\Entity\Role;
use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email')
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'ROLE_USER' => 'ROLE_USER',
                    'ROLE_STUDENT' => 'ROLE_STUDENT',
                    'ROLE_TEACHER' => 'ROLE_TEACHER',
                    'ROLE_ADMIN' => 'ROLE_ADMIN',
                ],
                'expanded' => true,
                'multiple' => true,
                'choice_value' => function ($role) {
                    return $role instanceof Role ? $role->value : $role;
                },
            ])
            ->add('password', null, [
                'required' => false,
            ])
            ->add('firstname')
            ->add('lastname')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
