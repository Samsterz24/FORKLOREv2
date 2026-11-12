<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Cuisine;
use App\Entity\Recipe;
use App\Entity\Story;
use App\Entity\User;
use App\Repository\RecipeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em, private RecipeRepository $recipeRepository)
    {
    }

    #[Route('/', name: 'home', methods: ['GET'])]
    public function index(): Response
    {
        // Leaderboard: recipes ranked by total favorites/likes, then recency
        $allRecipes = $this->recipeRepository->findBy([], ['totalLikes' => 'DESC', 'createdAt' => 'DESC']);
        $recipeOfTheWeek = $allRecipes[0] ?? null;
        $featuredRecipes = array_slice($allRecipes, 0, 6);

        $cuisineImageMap = [
            'filipino' => 'images/ph.jpg',
            'french' => 'images/fr.jpg',
            'indian' => 'images/india.jpg',
            'italian' => 'images/italian.jpg',
            'japanese' => 'images/jp.jpg',
            'mexican' => 'images/mex.jpg',
        ];

        $dbCuisines = $this->em->getRepository(Cuisine::class)->findBy([], ['name' => 'ASC']);
        if (count($dbCuisines) > 0) {
            $cuisines = array_map(static function (Cuisine $c) use ($cuisineImageMap): array {
                $slug = $c->getSlug();
                return [
                    'id' => $c->getId(),
                    'name' => $c->getName(),
                    'slug' => $slug,
                    'image' => $cuisineImageMap[$slug] ?? 'images/placeholder.jpg',
                ];
            }, $dbCuisines);
        } else {
            $cuisines = [
                ['id' => 1, 'name' => 'Filipino', 'slug' => 'filipino', 'image' => 'images/ph.jpg'],
                ['id' => 2, 'name' => 'French', 'slug' => 'french', 'image' => 'images/fr.jpg'],
                ['id' => 3, 'name' => 'Indian', 'slug' => 'indian', 'image' => 'images/india.jpg'],
                ['id' => 4, 'name' => 'Italian', 'slug' => 'italian', 'image' => 'images/italian.jpg'],
                ['id' => 5, 'name' => 'Japanese', 'slug' => 'japanese', 'image' => 'images/jp.jpg'],
                ['id' => 6, 'name' => 'Mexican', 'slug' => 'mexican', 'image' => 'images/mex.jpg'],
            ];
        }

        // Leaderboard: stories ranked by saves, then recency
        $storyLeaderboard = $this->em->getRepository(Story::class)->findBy([], ['savesCount' => 'DESC', 'createdAt' => 'DESC']);
        $featuredStory = $storyLeaderboard[0] ?? null;
        $otherStories = count($storyLeaderboard) > 1 ? array_slice($storyLeaderboard, 1, 4) : [];

        $stats = [
            'recipes' => (int) $this->em->getRepository(Recipe::class)->count([]),
            'cuisines' => (int) $this->em->getRepository(Cuisine::class)->count([]),
            'contributors' => (int) $this->em->getRepository(User::class)->count([]),
        ];

        return $this->render('home/index.html.twig', [
            'recipeOfTheWeek' => $recipeOfTheWeek,
            'featuredRecipes' => $featuredRecipes,
            'featuredStory' => $featuredStory,
            'otherStories' => $otherStories,
            'cuisines' => $cuisines,
            'stats' => $stats,
        ]);
    }
}
