<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ShareController extends AbstractController
{
    #[Route('/share/recipe', name: 'share_recipe', methods: ['GET', 'POST'])]
    public function recipe(Request $request): Response
    {
        $notice = null;
        if ($request->isMethod('POST')) {
            $notice = 'UI preview only — your recipe was accepted visually, but nothing was published or stored.';
        }

        // Cuisines and categories will come from repositories in the persistence
        // phase. The current user's stamp status will come from the authenticated user.
        return $this->render('share/recipe.html.twig', [
            'cuisines' => [],
            'categories' => [],
            'hasFoodieStamp' => false,
            'notice' => $notice,
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
