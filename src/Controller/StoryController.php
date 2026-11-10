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

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->em->getRepository(User::class)->findOneBy([]);
            if (!$user) {
                $user = new User();
                $user->setUsername('demo');
                $user->setEmail('demo@example.com');
                $user->setPassword('demo');
                $this->em->persist($user);
            }
            $story->setUser($user);
            $this->em->persist($story);
            $this->em->flush();
            return $this->redirectToRoute('stories_index');
        }

        return $this->render('stories/index.html.twig', [
            'featuredStory' => null,
            'otherStories' => $this->em->getRepository(Story::class)->findAll(),
            'cuisines' => $this->em->getRepository(Cuisine::class)->findAll(),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/stories/{id}', name: 'stories_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id): Response
    {
        return $this->render('stories/show.html.twig', [
            'story' => $this->em->getRepository(Story::class)->find($id),
        ]);
    }
}