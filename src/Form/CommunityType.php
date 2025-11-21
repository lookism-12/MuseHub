<?php

namespace App\Form;

use App\Entity\Community;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class CommunityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Community Name',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Enter community name'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['class' => 'form-control', 'rows' => 5, 'placeholder' => 'Describe your community...'],
                'required' => false,
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Category',
                'choices' => [
                    'Art' => 'art',
                    'Design' => 'design',
                    'Photography' => 'photography',
                    'Music' => 'music',
                    'Literature' => 'literature',
                    'Other' => 'other',
                ],
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
            ->add('imageUrl', TextType::class, [
                'label' => 'Image URL',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Enter image URL'],
                'required' => false,
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Active',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Community::class,
        ]);
    }
}
