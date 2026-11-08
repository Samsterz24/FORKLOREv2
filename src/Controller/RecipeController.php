<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RecipeController extends AbstractController
{
    private const ALLOWED_SORTS = ['relevance', 'recent', 'oldest', 'favorites'];
    private const ALLOWED_ALLERGIES = ['', 'peanut', 'tree_nut', 'milk', 'egg', 'fish', 'shellfish', 'soy', 'wheat'];

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

        // Recipes, cuisines, and categories will be loaded from repositories in
        // the persistence phase. The filters are already bound to those inputs.
        return $this->render('recipes/index.html.twig', [
            'recipes' => [],
            'cuisines' => [],
            'categories' => [],
            'search' => $search,
            'sort' => $sort,
            'category' => $category,
            'cuisine' => $cuisine,
            'allergy' => $allergy,
            'selectedCuisineName' => '',
        ]);
    }

    #[Route('/recipes/{id}', name: 'recipes_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id): Response
    {
        // A repository lookup will provide the recipe once persistence exists.
        return $this->render('recipes/show.html.twig', [
            'recipe' => null,
        ]);
    }

    #[Route('/recipes/{id}/edit', name: 'recipes_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request): Response
    {
        $notice = null;
        if ($request->isMethod('POST')) {
            $notice = 'UI preview only — this form accepted your input, but recipe changes are not saved yet.';
        }

        return $this->render('recipes/edit.html.twig', [
            'recipe' => null,
            'cuisines' => [],
            'categories' => [],
            'notice' => $notice,
        ]);
    }
}
