<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Cuisine;
use App\Entity\Recipe;
use App\Entity\User;
use App\Form\RecipeType;
use App\Repository\RecipeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RecipeController extends AbstractController
{
    private const ALLOWED_SORTS = ['relevance', 'recent', 'oldest', 'favorites'];
    private const ALLOWED_ALLERGIES = ['', 'peanut', 'tree_nut', 'milk', 'egg', 'fish', 'shellfish', 'soy', 'wheat'];
    private const DEFAULT_CATEGORIES = ['Breakfast', 'Lunch', 'Dinner', 'Dessert', 'Snack', 'Appetizer', 'General'];
    private const DRAFT_SESSION_KEY = 'recipe_draft';

    public function __construct(private EntityManagerInterface $em, private RecipeRepository $recipeRepository) {}

    #[Route('/recipes', name: 'recipes_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $sort = (string) $request->query->get('sort', 'recent');
        $category = (string) $request->query->get('category', 'all');
        $cuisine = (string) $request->query->get('cuisine', 'all');
        $allergy = (string) $request->query->get('allergy', '');
        $search = trim((string) $request->query->get('search', ''));

        if (!in_array($sort, self::ALLOWED_SORTS, true)) {
            $sort = 'recent';
        }
        if (!in_array($allergy, self::ALLOWED_ALLERGIES, true)) {
            $allergy = '';
        }

        $cuisines = $this->em->getRepository(Cuisine::class)->findAll();
        $recipes = $this->recipeRepository->findAll();
        $categories = array_values(array_unique(array_merge(
            self::DEFAULT_CATEGORIES,
            array_filter(array_map(fn($r) => $r->getCategory(), $recipes))
        )));
        sort($categories);

        return $this->render('recipes/index.html.twig', [
            'recipes' => $recipes,
            'cuisines' => $cuisines,
            'categories' => $categories,
            'search' => $search,
            'sort' => $sort,
            'category' => $category,
            'cuisine' => $cuisine,
            'allergy' => $allergy,
            'selectedCuisineName' => '',
        ]);
    }

    #[Route('/recipes/new', name: 'recipes_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
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
                $draftCuisine = $this->em->getRepository(Cuisine::class)->find((int) $draft['cuisine']);
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
                    $user = $this->em->getRepository(User::class)->findOneBy([]);
                    if (!$user) {
                        $user = new User();
                        $user->setUsername('demo');
                        $user->setEmail('demo@example.com');
                        $user->setPassword('demo');
                        $this->em->persist($user);
                    }
                }
                $recipe->setUser($user);
                $this->em->persist($recipe);
                $this->em->flush();

                $session->remove(self::DRAFT_SESSION_KEY);

                return $this->redirectToRoute('recipes_index');
            }
        }

        return $this->render('recipes/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/recipes/{id}', name: 'recipes_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id): Response
    {
        $recipe = $this->em->getRepository(Recipe::class)->find($id);
        return $this->render('recipes/show.html.twig', [
            'recipe' => $recipe,
        ]);
    }

    #[Route('/recipes/{id}/edit', name: 'recipes_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request): Response
    {
        $recipe = $this->em->getRepository(Recipe::class)->find($id);
        $notice = null;
        if ($request->isMethod('POST')) {
            $notice = 'UI preview only — this form accepted your input, but recipe changes are not saved yet.';
        }

        return $this->render('recipes/edit.html.twig', [
            'recipe' => $recipe,
            'cuisines' => $this->em->getRepository(Cuisine::class)->findAll(),
            'categories' => [],
            'notice' => $notice,
        ]);
    }
}