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
        yield 'home' => ['/forklore/'];
        yield 'recipes index' => ['/forklore/recipes'];
        yield 'recipes index with filters' => ['/forklore/recipes?sort=favorites&category=dinner&cuisine=italian&allergy=peanut&search=pasta'];
        yield 'recipe detail' => ['/forklore/recipes/1'];
        yield 'cuisines' => ['/forklore/cuisines'];
        yield 'stories index' => ['/forklore/stories'];
        yield 'story detail' => ['/forklore/stories/1'];
        yield 'community index' => ['/forklore/community'];
        yield 'community detail' => ['/forklore/community/1'];
        yield 'about' => ['/forklore/about'];
        yield 'contact' => ['/forklore/contact'];
        yield 'contact new ticket' => ['/forklore/contact?new=1'];
        yield 'contact selected ticket' => ['/forklore/contact?ticket_id=1042'];
        yield 'profile' => ['/forklore/profile'];
        yield 'profile saved tab' => ['/forklore/profile?tab=saved'];
        yield 'profile stories tab' => ['/forklore/profile?tab=stories'];
        yield 'login' => ['/forklore/login'];
        yield 'register' => ['/forklore/register'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function adminPages(): iterable
    {
        yield 'dashboard' => ['/forklore/admin'];
        yield 'dashboard daily leaderboard' => ['/forklore/admin?leaderboard=daily'];
        yield 'users' => ['/forklore/admin/users'];
        yield 'transactions' => ['/forklore/admin/transactions'];
        yield 'transactions filtered' => ['/forklore/admin/transactions?method=Mock+Card&status=completed&search=maria'];
        yield 'support' => ['/forklore/admin/support'];
        yield 'support open tickets' => ['/forklore/admin/support?status=open'];
        yield 'support selected ticket' => ['/forklore/admin/support?ticket_id=1041'];
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
        $client->request('GET', '/forklore/logout');

        $this->assertResponseRedirects('/forklore/');
    }

    public function testShareRecipeRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/forklore/share/recipe');

        $this->assertResponseRedirects('/forklore/login');
    }

    public function testRecipeEditRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/forklore/recipes/1/edit');

        $this->assertResponseRedirects('/forklore/login');
    }

    public function testContactPostRequiresLoginMessage(): void
    {
        $client = static::createClient();
        $client->request('POST', '/forklore/contact', ['action' => 'create_ticket', 'subject' => 'Help', 'message' => 'Hi']);

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Please log in', (string) $client->getResponse()->getContent());
    }

    public function testShareStoryRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/forklore/share/story');

        $this->assertResponseRedirects('/forklore/login');
    }

    public function testRecipeDetailShowsEmptyState(): void
    {
        $client = static::createClient();
        $client->request('GET', '/forklore/recipes/9999');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Recipe not available yet', (string) $client->getResponse()->getContent());
    }

    public function testStoryDetailShowsEmptyState(): void
    {
        $client = static::createClient();
        $client->request('GET', '/forklore/stories/9999');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('No story available yet', (string) $client->getResponse()->getContent());
    }

    public function testDiscussionDetailShowsEmptyState(): void
    {
        $client = static::createClient();
        $client->request('GET', '/forklore/community/9999');

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
        $client->request('GET', '/forklore/admin/users');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('User Management', (string) $client->getResponse()->getContent());
    }
}
