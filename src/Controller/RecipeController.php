<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Cuisine;
use App\Entity\Recipe;
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

    public function __construct(private EntityManagerInterface $em, private RecipeRepository $recipeRepository) {}

    private const REFERENCE_CUISINES = [
        ['id' => 1, 'name' => 'Filipino', 'slug' => 'filipino'],
        ['id' => 2, 'name' => 'French', 'slug' => 'french'],
        ['id' => 3, 'name' => 'Indian', 'slug' => 'indian'],
        ['id' => 4, 'name' => 'Italian', 'slug' => 'italian'],
        ['id' => 5, 'name' => 'Japanese', 'slug' => 'japanese'],
        ['id' => 6, 'name' => 'Mexican', 'slug' => 'mexican'],
    ];

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

        $dbCuisines = $this->em->getRepository(Cuisine::class)->findAll();
        $cuisines = self::REFERENCE_CUISINES;
        foreach ($dbCuisines as $dbCuisine) {
            $slug = strtolower($dbCuisine->getSlug());
            $exists = false;
            foreach ($cuisines as $c) {
                if (strtolower($c['slug']) === $slug) { $exists = true; break; }
            }
            if (!$exists) {
                $cuisines[] = ['id' => $dbCuisine->getId(), 'name' => $dbCuisine->getName(), 'slug' => $dbCuisine->getSlug()];
            }
        }

        $recipes = $this->recipeRepository->findAll();
        $categories = array_values(array_unique(array_merge(
            self::DEFAULT_CATEGORIES,
            array_filter(array_map(fn($r) => $r->getCategory(), $recipes))
        )));
        sort($categories);

        $selectedCuisineName = '';
        if ($cuisine !== '' && strtolower($cuisine) !== 'all') {
            foreach ($cuisines as $c) {
                if (strtolower($c['slug']) === strtolower($cuisine) || strtolower($c['name']) === strtolower($cuisine)) {
                    $selectedCuisineName = $c['name'];
                    $cuisine = $c['slug'];
                    break;
                }
            }
        }

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $recipes = array_values(array_filter($recipes, function ($r) use ($needle) {
                $hay = mb_strtolower(($r->getTitle() ?? '') . ' ' . ($r->getCategory() ?? '') . ' ' . ($r->getIngredients() ?? '') . ' ' . ($r->getUser()?->getUsername() ?? ''));
                $cuisineName = $r->getCuisine()?->getName() ?? '';
                return str_contains($hay, $needle) || str_contains(mb_strtolower($cuisineName), $needle);
            }));
        }
        if ($category !== '' && strtolower($category) !== 'all') {
            $recipes = array_values(array_filter($recipes, fn($r) => strcasecmp((string) $r->getCategory(), $category) === 0));
        }
        if ($cuisine !== '' && strtolower($cuisine) !== 'all') {
            $recipes = array_values(array_filter($recipes, function ($r) use ($cuisine, $selectedCuisineName) {
                $rc = $r->getCuisine();
                if (!$rc) { return false; }
                return strcasecmp($rc->getSlug(), $cuisine) === 0
                    || strcasecmp($rc->getName(), $cuisine) === 0
                    || ($selectedCuisineName !== '' && strcasecmp($rc->getName(), $selectedCuisineName) === 0);
            }));
        }

        if ($sort === 'oldest') {
            usort($recipes, fn($a, $b) => $a->getCreatedAt() <=> $b->getCreatedAt());
        } elseif ($sort === 'favorites') {
            usort($recipes, fn($a, $b) => $b->getTotalLikes() <=> $a->getTotalLikes());
        } else { // recent/relevance default
            usort($recipes, fn($a, $b) => $b->getCreatedAt() <=> $a->getCreatedAt());
        }

        return $this->render('recipes/index.html.twig', [
            'recipes' => $recipes,
            'cuisines' => $cuisines,
            'categories' => $categories,
            'search' => $search,
            'sort' => $sort,
            'category' => $category,
            'cuisine' => $cuisine,
            'allergy' => $allergy,
            'selectedCuisineName' => $selectedCuisineName,
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