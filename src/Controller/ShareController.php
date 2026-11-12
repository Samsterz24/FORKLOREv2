<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Cuisine;
use App\Entity\Recipe;
use App\Entity\User;
use App\Form\RecipeType;
use App\Service\RecipeImageStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ShareController extends AbstractController
{
    private const DRAFT_SESSION_KEY = 'recipe_draft';
    private const REFERENCE_CUISINES = [
        ['name' => 'Filipino', 'slug' => 'filipino'],
        ['name' => 'French', 'slug' => 'french'],
        ['name' => 'Indian', 'slug' => 'indian'],
        ['name' => 'Italian', 'slug' => 'italian'],
        ['name' => 'Japanese', 'slug' => 'japanese'],
        ['name' => 'Mexican', 'slug' => 'mexican'],
    ];

    #[Route('/share/recipe', name: 'share_recipe', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function recipe(Request $request, EntityManagerInterface $em, RecipeImageStorage $imageStorage): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $session = $request->getSession();
        $recipe = new Recipe();

        foreach (self::REFERENCE_CUISINES as $ref) {
            $existing = $em->getRepository(Cuisine::class)->findOneBy(['slug' => $ref['slug']]);
            if (!$existing) {
                $cuisine = new Cuisine();
                $cuisine->setName($ref['name']);
                $cuisine->setSlug($ref['slug']);
                $em->persist($cuisine);
            }
        }
        $em->flush();

        $draft = $session->get(self::DRAFT_SESSION_KEY, []);
        if ($request->isMethod('GET') && is_array($draft)) {
            if (!empty($draft['title'])) {
                $recipe->setTitle((string) $draft['title']);
            }
            if (!empty($draft['category'])) {
                $recipe->setCategory((string) $draft['category']);
            }
            if (!empty($draft['ingredients'])) {
                $recipe->setIngredients((string) $draft['ingredients']);
            }
            if (!empty($draft['cuisine'])) {
                $draftCuisine = $em->getRepository(Cuisine::class)->find((int) $draft['cuisine']);
                if ($draftCuisine) {
                    $recipe->setCuisine($draftCuisine);
                }
            }
        }

        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $posted = $request->request->all('recipe');
            if (is_array($posted)) {
                unset($posted['_token']);
                $session->set(self::DRAFT_SESSION_KEY, $posted);
            }

            $imageFile = $form->get('recipe_image')->getData();
            $imageError = $imageStorage->validateUploadedImage($imageFile);
            if ($imageError) {
                $form->get('recipe_image')->addError(new FormError($imageError));
            }

            if ($form->isValid()) {
                if ($imageFile) {
                    $recipe->setImageFilename($imageStorage->store($imageFile));
                }

                $recipe->setUser($user);
                $em->persist($recipe);
                $em->flush();

                $session->remove(self::DRAFT_SESSION_KEY);

                return $this->redirectToRoute('recipes_show', ['id' => $recipe->getId()]);
            }
        }

        $cuisines = $em->getRepository(Cuisine::class)->findBy([], ['name' => 'ASC']);

        return $this->render('share/recipe.html.twig', [
            'form' => $form->createView(),
            'cuisines' => $cuisines,
            'categories' => ['Breakfast', 'Lunch', 'Dinner', 'Dessert', 'Snack', 'Appetizer', 'General'],
            'hasFoodieStamp' => method_exists($user, 'hasFoodieStamp') ? $user->hasFoodieStamp() : false,
        ]);
    }

    #[Route('/share/story', name: 'share_story', methods: ['GET', 'POST'])]
    public function story(Request $request): Response
    {
        $notice = null;
        if ($request->isMethod('POST')) {
            $notice = 'UI preview only — your story was accepted visually, but nothing was published or stored.';
        }

        return $this->render('share/story.html.twig', [
            'cuisines' => [],
            'userRecipes' => [],
            'authorName' => '',
            'notice' => $notice,
        ]);
    }
}