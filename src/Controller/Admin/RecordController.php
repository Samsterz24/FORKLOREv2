<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Post;
use App\Entity\Recipe;
use App\Entity\Reply;
use App\Entity\Story;
use App\Entity\SupportTicket;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
final class RecordController extends AbstractController
{
    #[Route('/records', name: 'admin_records', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $notice = null;
        $error = null;

        if ($request->isMethod('POST')) {
            [$notice, $error] = $this->handleAction($request, $em);
        }

        $type = (string) $request->query->get('type', 'all');
        $search = trim((string) $request->query->get('search', ''));
        $allowedTypes = ['all', 'recipe', 'story', 'post', 'reply', 'support_ticket'];
        if (!in_array($type, $allowedTypes, true)) {
            $type = 'all';
        }

        $records = [];
        $records = array_merge($records, $type === 'all' || $type === 'recipe' ? $this->mapRecipes($em) : []);
        $records = array_merge($records, $type === 'all' || $type === 'story' ? $this->mapStories($em) : []);
        $records = array_merge($records, $type === 'all' || $type === 'post' ? $this->mapPosts($em) : []);
        $records = array_merge($records, $type === 'all' || $type === 'reply' ? $this->mapReplies($em) : []);
        $records = array_merge($records, $type === 'all' || $type === 'support_ticket' ? $this->mapTickets($em) : []);

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $records = array_values(array_filter($records, static function (array $r) use ($needle): bool {
                $hay = mb_strtolower(implode(' ', [
                    (string) ($r['id'] ?? ''),
                    (string) ($r['type'] ?? ''),
                    (string) ($r['title'] ?? ''),
                    (string) ($r['owner'] ?? ''),
                    (string) ($r['details'] ?? ''),
                ]));
                return str_contains($hay, $needle);
            }));
        }

        usort($records, static fn(array $a, array $b) => ($b['createdAtTs'] ?? 0) <=> ($a['createdAtTs'] ?? 0));

        return $this->render('admin/records.html.twig', [
            'records' => $records,
            'type' => $type,
            'search' => $search,
            'notice' => $notice,
            'error' => $error,
        ]);
    }

    /** @return array{0:?string,1:?string} */
    private function handleAction(Request $request, EntityManagerInterface $em): array
    {
        $action = (string) $request->request->get('action', '');
        $recordType = (string) $request->request->get('record_type', '');
        $id = (int) $request->request->get('record_id', 0);
        $token = (string) $request->request->get('_token', '');

        if (!$this->isCsrfTokenValid('admin_record_' . $action . '_' . $recordType . '_' . $id, $token)) {
            return [null, 'Invalid CSRF token. Please try again.'];
        }

        $map = [
            'recipe' => Recipe::class,
            'story' => Story::class,
            'post' => Post::class,
            'reply' => Reply::class,
            'support_ticket' => SupportTicket::class,
        ];
        if ($action !== 'delete' || !isset($map[$recordType])) {
            return [null, 'Unknown action.'];
        }

        $entity = $em->getRepository($map[$recordType])->find($id);
        if (!$entity) {
            return [null, 'Record not found.'];
        }

        $em->remove($entity);
        $em->flush();

        return [sprintf('Deleted %s #%d.', str_replace('_', ' ', $recordType), $id), null];
    }

    /** @return list<array<string,mixed>> */
    private function mapRecipes(EntityManagerInterface $em): array
    {
        $items = $em->getRepository(Recipe::class)->findBy([], ['createdAt' => 'DESC']);
        $out = [];
        foreach ($items as $r) {
            $out[] = [
                'type' => 'recipe',
                'id' => $r->getId(),
                'title' => $r->getTitle(),
                'owner' => $r->getUser()?->getUsername() ?? 'Unknown',
                'details' => $r->getCategory(),
                'createdAt' => $r->getCreatedAt(),
                'createdAtTs' => $r->getCreatedAt()->getTimestamp(),
                'viewUrl' => $this->generateUrl('recipes_show', ['id' => $r->getId()]),
                'editUrl' => $this->generateUrl('recipes_edit', ['id' => $r->getId()]),
            ];
        }
        return $out;
    }

    /** @return list<array<string,mixed>> */
    private function mapStories(EntityManagerInterface $em): array
    {
        $items = $em->getRepository(Story::class)->findBy([], ['createdAt' => 'DESC']);
        $out = [];
        foreach ($items as $s) {
            $out[] = [
                'type' => 'story',
                'id' => $s->getId(),
                'title' => $s->getTitle(),
                'owner' => $s->getUser()?->getUsername() ?? ($s->getAuthorName() ?: 'Unknown'),
                'details' => $s->getLocation() ?: 'Story',
                'createdAt' => $s->getCreatedAt(),
                'createdAtTs' => $s->getCreatedAt()->getTimestamp(),
                'viewUrl' => $this->generateUrl('stories_show', ['id' => $s->getId()]),
                'editUrl' => null,
            ];
        }
        return $out;
    }

    /** @return list<array<string,mixed>> */
    private function mapPosts(EntityManagerInterface $em): array
    {
        $items = $em->getRepository(Post::class)->findBy([], ['createdAt' => 'DESC']);
        $out = [];
        foreach ($items as $p) {
            $out[] = [
                'type' => 'post',
                'id' => $p->getId(),
                'title' => $p->getTitle(),
                'owner' => $p->getUser()?->getUsername() ?? 'Unknown',
                'details' => $p->getCategory(),
                'createdAt' => $p->getCreatedAt(),
                'createdAtTs' => $p->getCreatedAt()->getTimestamp(),
                'viewUrl' => $this->generateUrl('community_show', ['id' => $p->getId()]),
                'editUrl' => $this->generateUrl('community_show', ['id' => $p->getId()]),
            ];
        }
        return $out;
    }

    /** @return list<array<string,mixed>> */
    private function mapReplies(EntityManagerInterface $em): array
    {
        $items = $em->getRepository(Reply::class)->findBy([], ['createdAt' => 'DESC']);
        $out = [];
        foreach ($items as $r) {
            $comment = trim((string) $r->getComment());
            $title = mb_substr($comment, 0, 60) . (mb_strlen($comment) > 60 ? '...' : '');
            $out[] = [
                'type' => 'reply',
                'id' => $r->getId(),
                'title' => $title !== '' ? $title : 'Reply',
                'owner' => $r->getUser()?->getUsername() ?? 'Unknown',
                'details' => 'Post #' . ($r->getPost()?->getId() ?? '?'),
                'createdAt' => $r->getCreatedAt(),
                'createdAtTs' => $r->getCreatedAt()->getTimestamp(),
                'viewUrl' => $r->getPost() ? $this->generateUrl('community_show', ['id' => $r->getPost()->getId()]) . '#replies' : null,
                'editUrl' => null,
            ];
        }
        return $out;
    }

    /** @return list<array<string,mixed>> */
    private function mapTickets(EntityManagerInterface $em): array
    {
        $items = $em->getRepository(SupportTicket::class)->findBy([], ['createdAt' => 'DESC']);
        $out = [];
        foreach ($items as $t) {
            $out[] = [
                'type' => 'support_ticket',
                'id' => $t->getId(),
                'title' => $t->getSubject(),
                'owner' => $t->getUser()?->getUsername() ?? 'Unknown',
                'details' => $t->getStatus(),
                'createdAt' => $t->getCreatedAt(),
                'createdAtTs' => $t->getCreatedAt()->getTimestamp(),
                'viewUrl' => $this->generateUrl('admin_support', ['ticket_id' => $t->getId()]),
                'editUrl' => null,
            ];
        }
        return $out;
    }
}
