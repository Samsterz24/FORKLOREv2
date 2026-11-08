<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CommunityController extends AbstractController
{
    #[Route('/community', name: 'community_index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $notice = null;

        if ($request->isMethod('POST')) {
            $notice = 'UI preview only — your thread was received visually, but discussions are not persisted yet.';
        }

        // Posts and categories will be provided by repositories once the
        // persistence layer exists. The templates already loop over both lists.
        return $this->render('community/index.html.twig', [
            'posts' => [],
            'categories' => [],
            'notice' => $notice,
        ]);
    }

    #[Route('/community/{id}', name: 'community_show', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function show(int $id, Request $request): Response
    {
        $notice = null;
        if ($request->isMethod('POST')) {
            $notice = 'UI preview only — your reply was received visually, but it is not stored yet.';
        }

        // A repository lookup will provide the discussion once persistence exists.
        return $this->render('community/show.html.twig', [
            'post' => null,
            'notice' => $notice,
        ]);
    }
}
