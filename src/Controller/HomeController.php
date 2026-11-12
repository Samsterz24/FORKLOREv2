<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Cuisine;
use App\Entity\Recipe;
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
        $allRecipes = $this->recipeRepository->findBy([], ['totalLikes' => 'DESC', 'createdAt' => 'DESC']);
        $recipeOfTheWeek = $allRecipes[0] ?? null;
        $featuredRecipes = array_slice($allRecipes, 0, 6);

        $cuisines = $this->em->getRepository(Cuisine::class)->findBy([], ['name' => 'ASC']);
        if (count($cuisines) === 0) {
            $cuisines = [
                (object) ['id' => 1, 'name' => 'Filipino', 'slug' => 'filipino', 'image' => 'images/ph.jpg'],
                (object) ['id' => 2, 'name' => 'French', 'slug' => 'french', 'image' => 'images/fr.jpg'],
                (object) ['id' => 3, 'name' => 'Indian', 'slug' => 'indian', 'image' => 'images/india.jpg'],
                (object) ['id' => 4, 'name' => 'Italian', 'slug' => 'italian', 'image' => 'images/italian.jpg'],
                (object) ['id' => 5, 'name' => 'Japanese', 'slug' => 'japanese', 'image' => 'images/jp.jpg'],
                (object) ['id' => 6, 'name' => 'Mexican', 'slug' => 'mexican', 'image' => 'images/mex.jpg'],
            ];
        }

        $stats = [
            'recipes' => (int) $this->em->getRepository(Recipe::class)->count([]),
            'cuisines' => (int) $this->em->getRepository(Cuisine::class)->count([]),
            'contributors' => (int) $this->em->getRepository(User::class)->count([]),
        ];

        return $this->render('home/index.html.twig', [
            'recipeOfTheWeek' => $recipeOfTheWeek,
            'featuredRecipes' => $featuredRecipes,
            'featuredStory' => null,
            'otherStories' => [],
            'cuisines' => $cuisines,
            'stats' => $stats,
        ]);
    }
}
