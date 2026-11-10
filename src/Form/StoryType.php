<?php

namespace App\Form;

use App\Entity\Cuisine;
use App\Entity\Story;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class)
            ->add('location', TextType::class)
            ->add('authorName', TextType::class)
            ->add('quote', TextareaType::class, ['required' => false])
            ->add('cuisine', EntityType::class, [
                'class' => Cuisine::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'Select a cuisine',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Story::class]);
    }
}