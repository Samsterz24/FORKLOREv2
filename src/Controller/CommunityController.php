<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Reply;
use App\Entity\User;
use App\Form\PostType;
use App\Repository\ReplyRepository;
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
            if ($user instanceof User) {
                $post->setUser($user);
                $this->em->persist($post);
                $this->em->flush();
                return $this->redirectToRoute('community_index');
            }
        }

        $posts = $this->em->getRepository(Post::class)->findAll();
        if ($search !== '') {
            $posts = array_values(array_filter($posts, fn($p) =>
                stripos($p->getTitle(), $search) !== false ||
                stripos($p->getContent(), $search) !== false ||
                stripos($p->getUser()->getUsername(), $search) !== false
            ));
        }

        $categories = ['General', 'Recipe Help', 'Tips & Tricks', 'Equipment'];

        return $this->render('community/index.html.twig', [
            'posts' => $posts,
            'categories' => $categories,
            'notice' => null,
            'search' => $search,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/community/{id}', name: 'community_show', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function show(int $id, Request $request, ReplyRepository $replyRepository): Response
    {
        $post = $this->em->getRepository(Post::class)->find($id);
        $notice = null;
        $error = null;

        if ($post && $request->isMethod('POST')) {
            $user = $this->getUser();
            $action = (string) $request->request->get('action', 'reply');
            $token = (string) $request->request->get('_token', '');

            if ($action === 'edit_post') {
                if (!$this->canManagePost($user, $post)) {
                    $error = 'You do not have permission to edit this post.';
                } elseif (!$this->isCsrfTokenValid('community_edit_post_' . $post->getId(), $token)) {
                    $error = 'Invalid CSRF token. Please try again.';
                } else {
                    $title = trim((string) $request->request->get('title', $post->getTitle()));
                    $category = trim((string) $request->request->get('category', $post->getCategory()));
                    $content = trim((string) $request->request->get('content', $post->getContent()));
                    if ($title === '' || $content === '') {
                        $error = 'Title and content are required.';
                    } else {
                        $post->setTitle($title);
                        $post->setCategory($category !== '' ? $category : $post->getCategory());
                        $post->setContent($content);
                        $this->em->flush();
                        $this->addFlash('success', 'Post updated successfully.');
                        return $this->redirectToRoute('community_show', ['id' => $post->getId()]);
                    }
                }
            } else {
                $comment = trim((string) $request->request->get('comment', ''));

                if (!$user instanceof User) {
                    $error = 'Please log in to reply.';
                } elseif (!$this->isCsrfTokenValid('community_reply_' . $post->getId(), $token)) {
                    $error = 'Invalid CSRF token. Please try again.';
                } elseif ($comment === '') {
                    $error = 'Please enter a reply.';
                } else {
                    $reply = new Reply();
                    $reply->setPost($post);
                    $reply->setUser($user);
                    $reply->setComment($comment);
                    $this->em->persist($reply);
                    $this->em->flush();

                    return $this->redirectToRoute('community_show', ['id' => $post->getId(), '_fragment' => 'replies']);
                }
            }
        }

        $replies = $post ? $replyRepository->findForPost($post) : [];
        $canManagePost = $this->canManagePost($this->getUser(), $post);

        return $this->render('community/show.html.twig', [
            'post' => $post,
            'replies' => $replies,
            'notice' => $notice,
            'error' => $error,
            'canManagePost' => $canManagePost,
            'categories' => ['General', 'Recipe Help', 'Tips & Tricks', 'Equipment'],
        ]);
    }

    #[Route('/community/{id}/delete', name: 'community_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id, Request $request): Response
    {
        $post = $this->em->getRepository(Post::class)->find($id);
        if (!$post) {
            throw $this->createNotFoundException();
        }

        $user = $this->getUser();
        if (!$this->canManagePost($user, $post)) {
            throw $this->createAccessDeniedException();
        }

        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('community_delete_post_' . $post->getId(), $token)) {
            $this->addFlash('error', 'Invalid delete request.');
            return $this->redirectToRoute('community_show', ['id' => $post->getId()]);
        }

        $this->em->remove($post);
        $this->em->flush();
        $this->addFlash('success', 'Post deleted successfully.');

        return $this->redirectToRoute('community_index');
    }

    private function canManagePost(?object $user, ?Post $post): bool
    {
        if (!$post || !$user instanceof User) {
            return false;
        }
        if ($this->isGranted('ROLE_ADMIN')) {
            return true;
        }
        $owner = $post->getUser();
        return $owner && $owner->getId() === $user->getId();
    }
}