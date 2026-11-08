<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CuisineController extends AbstractController
{
    #[Route('/cuisines', name: 'cuisines_index', methods: ['GET'])]
    public function index(): Response
    {
        // Reference list of cuisine categories. These will be loaded from the
        // cuisines repository once persistence is implemented; recipe counts
        // stay at zero until real recipes exist.
        $cuisines = [
            ['id' => 1, 'name' => 'Filipino', 'slug' => 'filipino', 'description' => 'Rich, savory, and tangy dishes shaped by centuries of tradition.', 'image' => 'images/ph.jpg', 'recipeCount' => 0],
            ['id' => 2, 'name' => 'French', 'slug' => 'french', 'description' => 'Classic techniques, buttery pastries, and refined bistro favorites.', 'image' => 'images/fr.jpg', 'recipeCount' => 0],
            ['id' => 3, 'name' => 'Indian', 'slug' => 'indian', 'description' => 'Aromatic spices, bold curries, and soulful family recipes.', 'image' => 'images/india.jpg', 'recipeCount' => 0],
            ['id' => 4, 'name' => 'Italian', 'slug' => 'italian', 'description' => 'Hearty pastas, rustic sauces, and timeless Mediterranean flavors.', 'image' => 'images/italian.jpg', 'recipeCount' => 0],
            ['id' => 5, 'name' => 'Japanese', 'slug' => 'japanese', 'description' => 'Delicate flavors, comforting broths, and culinary precision.', 'image' => 'images/jp.jpg', 'recipeCount' => 0],
            ['id' => 6, 'name' => 'Mexican', 'slug' => 'mexican', 'description' => 'Vibrant salsas, earthy chilies, and spirited traditional cooking.', 'image' => 'images/mex.jpg', 'recipeCount' => 0],
        ];

        return $this->render('cuisines/index.html.twig', [
            'cuisines' => $cuisines,
        ]);
    }
}
