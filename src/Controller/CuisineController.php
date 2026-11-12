<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Cuisine;
use App\Entity\Recipe;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CuisineController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    #[Route('/cuisines', name: 'cuisines_index', methods: ['GET'])]
    public function index(): Response
    {
        $defaults = [
            'filipino' => ['description' => 'Rich, savory, and tangy dishes shaped by centuries of tradition.', 'image' => 'images/ph.jpg'],
            'french' => ['description' => 'Classic techniques, buttery pastries, and refined bistro favorites.', 'image' => 'images/fr.jpg'],
            'indian' => ['description' => 'Aromatic spices, bold curries, and soulful family recipes.', 'image' => 'images/india.jpg'],
            'italian' => ['description' => 'Hearty pastas, rustic sauces, and timeless Mediterranean flavors.', 'image' => 'images/italian.jpg'],
            'japanese' => ['description' => 'Delicate flavors, comforting broths, and culinary precision.', 'image' => 'images/jp.jpg'],
            'mexican' => ['description' => 'Vibrant salsas, earthy chilies, and spirited traditional cooking.', 'image' => 'images/mex.jpg'],
        ];

        $rows = $this->em->createQueryBuilder()
            ->select('c AS cuisine', 'COUNT(r.id) AS recipeCount')
            ->from(Cuisine::class, 'c')
            ->leftJoin(Recipe::class, 'r', 'WITH', 'r.cuisine = c')
            ->groupBy('c.id')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();

        $cuisines = [];
        foreach ($rows as $row) {
            /** @var Cuisine $c */
            $c = $row['cuisine'];
            $slug = $c->getSlug();
            $meta = $defaults[$slug] ?? ['description' => $c->getDescription(), 'image' => 'images/placeholder.jpg'];
            $image = $meta['image'] ?? 'images/placeholder.jpg';
            $description = $c->getDescription() ?: ($meta['description'] ?? null);
            $cuisines[] = [
                'id' => $c->getId(),
                'name' => $c->getName(),
                'slug' => $slug,
                'description' => $description,
                'image' => $image,
                'recipeCount' => (int) ($row['recipeCount'] ?? 0),
            ];
        }

        // Fallback to reference list if DB has no cuisines yet
        if (count($cuisines) === 0) {
            foreach ($defaults as $slug => $meta) {
                $cuisines[] = [
                    'id' => null,
                    'name' => ucfirst($slug),
                    'slug' => $slug,
                    'description' => $meta['description'],
                    'image' => $meta['image'],
                    'recipeCount' => 0,
                ];
            }
        }

        return $this->render('cuisines/index.html.twig', [
            'cuisines' => $cuisines,
        ]);
    }
}
