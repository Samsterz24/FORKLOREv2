<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Cuisine;
use App\Entity\Favorite;
use App\Entity\Recipe;
use App\Entity\User;
use App\Form\RecipeType;
use App\Repository\RecipeRepository;
use App\Service\RecipeImageStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

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

    private function canManageRecipe(?object $user, Recipe $recipe): bool
    {
        if (!$user instanceof User) {
            return false;
        }
        if ($this->isGranted('ROLE_ADMIN')) {
            return true;
        }
        $owner = $recipe->getUser();
        return $owner && $owner->getId() === $user->getId();
    }

    #[Route('/recipes', name: 'recipes_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $sort = $request->query->getString('sort', 'relevance');
        $category = trim($request->query->getString('category', 'all'));
        $cuisine = trim($request->query->getString('cuisine', 'all'));
        $allergy = trim($request->query->getString('allergy', ''));
        $search = trim($request->query->getString('search', ''));

        if (!in_array($sort, self::ALLOWED_SORTS, true)) {
            $sort = 'relevance';
        }
        if ($category === '') {
            $category = 'all';
        }
        if ($cuisine === '') {
            $cuisine = 'all';
        }
        $allergy = strtolower($allergy);
        if (!in_array($allergy, self::ALLOWED_ALLERGIES, true)) {
            $allergy = '';
        }

        $cuisines = $this->em->getRepository(Cuisine::class)->findBy([], ['name' => 'ASC']);
        $recipes = $this->recipeRepository->searchAndFilter($search, $sort, $category, $cuisine, $allergy);

        $categories = self::DEFAULT_CATEGORIES;
        $categoryRows = $this->em->createQuery('SELECT DISTINCT r.category FROM App\\Entity\\Recipe r WHERE r.category IS NOT NULL AND r.category <> :empty ORDER BY r.category ASC')
            ->setParameter('empty', '')
            ->getScalarResult();
        foreach ($categoryRows as $row) {
            $value = trim((string) ($row['category'] ?? ''));
            if ($value !== '') {
                $categories[] = $value;
            }
        }
        $categories = array_values(array_unique($categories));
        sort($categories, SORT_NATURAL | SORT_FLAG_CASE);

        $selectedCuisineName = '';
        $cuisineLower = strtolower($cuisine);
        if ($cuisineLower !== '' && $cuisineLower !== 'all') {
            foreach ($cuisines as $cuisineOption) {
                if (strtolower($cuisineOption->getSlug()) === $cuisineLower || strtolower($cuisineOption->getName()) === $cuisineLower) {
                    $selectedCuisineName = $cuisineOption->getName();
                    break;
                }
            }
            if ($selectedCuisineName === '') {
                foreach (self::REFERENCE_CUISINES as $ref) {
                    if (strtolower($ref['slug']) === $cuisineLower || strtolower($ref['name']) === $cuisineLower) {
                        $selectedCuisineName = $ref['name'];
                        break;
                    }
                }
            }
            if ($selectedCuisineName === '') {
                $selectedCuisineName = ucwords(str_replace(['-', '_'], ' ', $cuisineLower));
            }
        }

        $cuisineOptions = $cuisines;
        if (count($cuisineOptions) === 0) {
            $cuisineOptions = array_map(static fn(array $ref): object => (object) $ref, self::REFERENCE_CUISINES);
        }

        return $this->render('recipes/index.html.twig', [
            'recipes' => $recipes,
            'cuisines' => $cuisineOptions,
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
        $user = $this->getUser();
        $canManage = $recipe ? $this->canManageRecipe($user, $recipe) : false;
        $isFavorited = false;
        if ($recipe && $user instanceof User) {
            $isFavorited = (bool) $this->em->getRepository(Favorite::class)->findOneBy([
                'user' => $user,
                'recipe' => $recipe,
            ]);
        }

        return $this->render('recipes/show.html.twig', [
            'recipe' => $recipe,
            'canManage' => $canManage,
            'isFavorited' => $isFavorited,
        ]);
    }

    #[Route('/recipes/{id}/favorite', name: 'recipes_favorite', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function toggleFavorite(int $id, Request $request, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('login');
        }

        $recipe = $this->em->getRepository(Recipe::class)->find($id);
        if (!$recipe) {
            throw $this->createNotFoundException();
        }

        $token = new CsrfToken('favorite_recipe_' . $recipe->getId(), (string) $request->request->get('_token'));
        if (!$csrfTokenManager->isTokenValid($token)) {
            $this->addFlash('error', 'Invalid or expired favorite request.');
            return $this->redirectToRoute('recipes_show', ['id' => $recipe->getId()]);
        }

        $favoriteRepo = $this->em->getRepository(Favorite::class);
        $existing = $favoriteRepo->findOneBy(['user' => $user, 'recipe' => $recipe]);

        if ($existing) {
            $this->em->remove($existing);
            $recipe->setTotalLikes(max(0, $recipe->getTotalLikes() - 1));
        } else {
            $favorite = new Favorite();
            $favorite->setUser($user);
            $favorite->setRecipe($recipe);
            $this->em->persist($favorite);
            $recipe->setTotalLikes($recipe->getTotalLikes() + 1);
        }

        $this->em->flush();

        return $this->redirectToRoute('recipes_show', ['id' => $recipe->getId()]);
    }

    #[Route('/recipes/{id}/edit', name: 'recipes_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, RecipeImageStorage $imageStorage): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('login');
        }

        $recipe = $this->em->getRepository(Recipe::class)->find($id);
        if (!$recipe) {
            throw $this->createNotFoundException();
        }
        if (!$this->canManageRecipe($user, $recipe)) {
            throw $this->createAccessDeniedException();
        }

        foreach (self::REFERENCE_CUISINES as $ref) {
            $existing = $this->em->getRepository(Cuisine::class)->findOneBy(['slug' => $ref['slug']]);
            if (!$existing) {
                $cuisine = new Cuisine();
                $cuisine->setName($ref['name']);
                $cuisine->setSlug($ref['slug']);
                $this->em->persist($cuisine);
            }
        }
        $this->em->flush();

        $originalFilename = $recipe->getImageFilename();
        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $imageFile = $form->get('recipe_image')->getData();
            $imageError = $imageStorage->validateUploadedImage($imageFile);
            if ($imageError) {
                $form->get('recipe_image')->addError(new FormError($imageError));
            }

            if ($form->isValid()) {
                if ($imageFile) {
                    $newFilename = $imageStorage->store($imageFile);
                    $recipe->setImageFilename($newFilename);

                    $others = (int) $this->recipeRepository->createQueryBuilder('r')
                        ->select('COUNT(r.id)')
                        ->andWhere('r.imageFilename = :name')
                        ->andWhere('r.id != :id')
                        ->setParameter('name', $originalFilename)
                        ->setParameter('id', $recipe->getId())
                        ->getQuery()->getSingleScalarResult();
                    if ($others === 0 && $originalFilename !== $newFilename) {
                        $imageStorage->deleteIfSafe($originalFilename);
                    }
                }

                $this->em->flush();
                $this->addFlash('success', 'Recipe updated successfully.');

                return $this->redirectToRoute('recipes_show', ['id' => $recipe->getId()]);
            }
        }

        return $this->render('recipes/edit.html.twig', [
            'recipe' => $recipe,
            'form' => $form->createView(),
            'categories' => self::DEFAULT_CATEGORIES,
        ]);
    }

    #[Route('/recipes/{id}/delete', name: 'recipes_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id, Request $request, RecipeImageStorage $imageStorage, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('login');
        }

        $recipe = $this->em->getRepository(Recipe::class)->find($id);
        if (!$recipe) {
            throw $this->createNotFoundException();
        }
        if (!$this->canManageRecipe($user, $recipe)) {
            throw $this->createAccessDeniedException();
        }

        $token = new CsrfToken('delete_recipe_' . $recipe->getId(), (string) $request->request->get('_token'));
        if (!$csrfTokenManager->isTokenValid($token)) {
            $this->addFlash('error', 'Invalid or expired delete request.');
            return $this->redirectToRoute('recipes_show', ['id' => $recipe->getId()]);
        }

        $filename = $recipe->getImageFilename();
        $others = (int) $this->recipeRepository->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.imageFilename = :name')
            ->andWhere('r.id != :id')
            ->setParameter('name', $filename)
            ->setParameter('id', $recipe->getId())
            ->getQuery()->getSingleScalarResult();

        $this->em->remove($recipe);
        $this->em->flush();

        if ($others === 0) {
            $imageStorage->deleteIfSafe($filename);
        }

        $this->addFlash('success', 'Recipe deleted successfully.');
        return $this->redirectToRoute('recipes_index');
    }
}