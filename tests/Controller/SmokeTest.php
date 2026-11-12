<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Guards the UI migration: every page must render successfully, use the
 * Symfony router instead of the original PHP files, and never leak a link to
 * a legacy *.php endpoint.
 */
final class SmokeTest extends WebTestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function publicPages(): iterable
    {
        yield 'home' => ['/'];
        yield 'recipes index' => ['/recipes'];
        yield 'recipes index with filters' => ['/recipes?sort=favorites&category=dinner&cuisine=italian&allergy=peanut&search=pasta'];
        yield 'recipe detail' => ['/recipes/1'];
        yield 'cuisines' => ['/cuisines'];
        yield 'stories index' => ['/stories'];
        yield 'story detail' => ['/stories/1'];
        yield 'community index' => ['/community'];
        yield 'community detail' => ['/community/1'];
        yield 'about' => ['/about'];
        yield 'contact' => ['/contact'];
        yield 'contact new ticket' => ['/contact?new=1'];
        yield 'contact selected ticket' => ['/contact?ticket_id=1042'];
        yield 'profile' => ['/profile'];
        yield 'profile saved tab' => ['/profile?tab=saved'];
        yield 'profile stories tab' => ['/profile?tab=stories'];
        yield 'login' => ['/login'];
        yield 'register' => ['/register'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function adminPages(): iterable
    {
        yield 'dashboard' => ['/admin'];
        yield 'dashboard daily leaderboard' => ['/admin?leaderboard=daily'];
        yield 'users' => ['/admin/users'];
        yield 'transactions' => ['/admin/transactions'];
        yield 'transactions filtered' => ['/admin/transactions?method=Mock+Card&status=completed&search=maria'];
        yield 'support' => ['/admin/support'];
        yield 'support open tickets' => ['/admin/support?status=open'];
        yield 'support selected ticket' => ['/admin/support?ticket_id=1041'];
    }

    #[DataProvider('publicPages')]
    public function testPublicPagesRender(string $path): void
    {
        $client = static::createClient();
        $client->request('GET', $path);

        $this->assertResponseIsSuccessful();

        $content = (string) $client->getResponse()->getContent();
        $this->assertStringNotContainsString('href="index.php', $content);
        $this->assertDoesNotMatchRegularExpression('/href="[^"]*\.php(?:[?"#]|")/i', $content);
    }

    #[DataProvider('adminPages')]
    public function testAdminPagesRender(string $path): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $user = $container->get(\App\Repository\UserRepository::class)->findOneBy(['username' => 'Samsterz24']);
        if ($user) {
            $client->loginUser($user);
        }
        $client->request('GET', $path);

        $this->assertResponseIsSuccessful();

        $content = (string) $client->getResponse()->getContent();
        $this->assertStringContainsString('admin-sidebar', $content);
        $this->assertDoesNotMatchRegularExpression('/href="[^"]*\.php(?:[?"#]|")/i', $content);
    }

    public function testLogoutRedirectsHome(): void
    {
        $client = static::createClient();
        $client->request('GET', '/logout');

        $this->assertResponseRedirects('/');
    }

    public function testShareRecipeRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/share/recipe');

        $this->assertResponseRedirects('/login');
    }

    public function testRecipeEditRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/recipes/1/edit');

        $this->assertResponseRedirects('/login');
    }

    public function testContactPostRequiresLoginMessage(): void
    {
        $client = static::createClient();
        $client->request('POST', '/contact', ['action' => 'create_ticket', 'subject' => 'Help', 'message' => 'Hi']);

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Please log in', (string) $client->getResponse()->getContent());
    }

    public function testShareStoryRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/share/story');

        $this->assertResponseRedirects('/login');
    }

    public function testRecipeDetailShowsEmptyState(): void
    {
        $client = static::createClient();
        $client->request('GET', '/recipes/9999');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Recipe not available yet', (string) $client->getResponse()->getContent());
    }

    public function testStoryDetailShowsEmptyState(): void
    {
        $client = static::createClient();
        $client->request('GET', '/stories/9999');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('No story available yet', (string) $client->getResponse()->getContent());
    }

    public function testDiscussionDetailShowsEmptyState(): void
    {
        $client = static::createClient();
        $client->request('GET', '/community/9999');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('No discussion available yet', (string) $client->getResponse()->getContent());
    }

    public function testAdminUsersPageRenders(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $user = $container->get(\App\Repository\UserRepository::class)->findOneBy(['username' => 'Samsterz24']);
        if ($user) {
            $client->loginUser($user);
        }
        $client->request('GET', '/admin/users');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('User Management', (string) $client->getResponse()->getContent());
    }
}
