<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Cuisine;
use App\Entity\Story;
use App\Entity\User;
use App\Form\StoryType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StoryController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em) {}

    #[Route('/stories', name: 'stories_index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $story = new Story();
        $form = $this->createForm(StoryType::class, $story);
        $form->handleRequest($request);

        $user = $this->getUser();
        if ($form->isSubmitted() && $form->isValid() && $user instanceof User) {
            $story->setUser($user);
            $story->setAuthorName($user->getUsername());
            $this->em->persist($story);
            $this->em->flush();
            return $this->redirectToRoute('stories_index');
        }

        $stories = $this->em->getRepository(Story::class)->findBy([], ['createdAt' => 'DESC']);

        return $this->render('stories/index.html.twig', [
            'featuredStory' => $stories[0] ?? null,
            'otherStories' => count($stories) > 1 ? array_slice($stories, 1) : [],
            'cuisines' => $this->em->getRepository(Cuisine::class)->findAll(),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/stories/{id}', name: 'stories_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id): Response
    {
        $story = $this->em->getRepository(Story::class)->find($id);
        $extra = [];
        if ($story && $story->getDataFile()) {
            $decoded = json_decode((string) $story->getDataFile(), true);
            if (is_array($decoded)) {
                $extra = $decoded;
            }
        }

        return $this->render('stories/show.html.twig', [
            'story' => $story,
            'narrative' => is_string($extra['narrative'] ?? null) ? $extra['narrative'] : ($story?->getQuote() ?? ''),
            'highlights' => is_array($extra['highlights'] ?? null) ? $extra['highlights'] : [],
        ]);
    }
}
