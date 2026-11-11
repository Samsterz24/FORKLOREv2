<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Form\PostType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CommunityController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em) {}

    #[Route('/community', name: 'community_index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query->get('search', ''));

        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $user = $this->em->getRepository(User::class)->findOneBy([]);
                if (!$user) {
                    $user = new User();
                    $user->setUsername('demo');
                    $user->setEmail('demo@example.com');
                    $user->setPassword('demo');
                    $this->em->persist($user);
                }
            }
            $post->setUser($user);
            $this->em->persist($post);
            $this->em->flush();
            return $this->redirectToRoute('community_index');
        }

        $posts = $this->em->getRepository(Post::class)->findAll();
        if ($search !== '') {
            $posts = array_values(array_filter($posts, fn($p) =>
                stripos($p->getTitle(), $search) !== false ||
                stripos($p->getContent(), $search) !== false ||
                stripos($p->getUser()->getUsername(), $search) !== false
            ));
        }

        return $this->render('community/index.html.twig', [
            'posts' => $posts,
            'categories' => [],
            'notice' => null,
            'search' => $search,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/community/{id}', name: 'community_show', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function show(int $id, Request $request): Response
    {
        $post = $this->em->getRepository(Post::class)->find($id);
        return $this->render('community/show.html.twig', [
            'post' => $post,
            'notice' => null,
        ]);
    }
}