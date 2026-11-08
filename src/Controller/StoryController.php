<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StoryController extends AbstractController
{
    #[Route('/stories', name: 'stories_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('stories/index.html.twig', [
            'featuredStory' => null,
            'otherStories' => [],
        ]);
    }

    #[Route('/stories/{id}', name: 'stories_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id): Response
    {
        // A repository lookup will provide the story once persistence exists.
        return $this->render('stories/show.html.twig', [
            'story' => null,
        ]);
    }
}
