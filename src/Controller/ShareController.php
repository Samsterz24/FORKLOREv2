<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Cuisine;
use App\Entity\Recipe;
use App\Entity\User;
use App\Form\RecipeType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ShareController extends AbstractController
{
    private const DRAFT_SESSION_KEY = 'recipe_draft';

    #[Route('/share/recipe', name: 'share_recipe', methods: ['GET', 'POST'])]
    public function recipe(Request $request, EntityManagerInterface $em): Response
    {
        $session = $request->getSession();
        $recipe = new Recipe();

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

            if ($form->isValid()) {
                $user = $this->getUser();
                if (!$user instanceof User) {
                    $user = $em->getRepository(User::class)->findOneBy([]);
                    if (!$user) {
                        $user = new User();
                        $user->setUsername('demo');
                        $user->setEmail('demo@example.com');
                        $user->setPassword('demo');
                        $em->persist($user);
                    }
                }
                $recipe->setUser($user);
                $em->persist($recipe);
                $em->flush();

                $session->remove(self::DRAFT_SESSION_KEY);

                return $this->redirectToRoute('recipes_index');
            }
        }

        return $this->render('share/recipe.html.twig', [
            'form' => $form->createView(),
            'cuisines' => $em->getRepository(Cuisine::class)->findAll(),
            'categories' => ['Breakfast', 'Lunch', 'Dinner', 'Dessert', 'Snack', 'Appetizer', 'General'],
            'hasFoodieStamp' => false,
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
