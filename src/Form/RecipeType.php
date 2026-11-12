<?php

namespace App\Form;

use App\Entity\Cuisine;
use App\Entity\Recipe;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class RecipeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $categories = ['Breakfast', 'Lunch', 'Dinner', 'Dessert', 'Snack', 'Appetizer', 'General'];

        $builder
            ->add('title', TextType::class)
            ->add('category', ChoiceType::class, [
                'choices' => array_combine($categories, $categories),
                'placeholder' => 'Select a category',
            ])
            ->add('cuisine', EntityType::class, [
                'class' => Cuisine::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'Select a cuisine',
            ])
            ->add('ingredients', TextareaType::class, ['required' => false])
            ->add('instructions', TextareaType::class, ['required' => false])
            ->add('recipe_image', FileType::class, [
                'label' => 'Recipe Image',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File(
                        maxSize: '5M',
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Recipe::class,
        ]);
    }
}