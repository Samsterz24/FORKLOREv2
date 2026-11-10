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
        yield 'recipe edit' => ['/recipes/1/edit'];
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
        yield 'share recipe' => ['/share/recipe'];
        yield 'share story' => ['/share/story'];
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

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function previewForms(): iterable
    {
        yield 'register' => ['/register', 'account creation'];
        yield 'recipe edit' => ['/recipes/1/edit', 'recipe changes'];
        yield 'share recipe' => ['/share/recipe', 'nothing was published'];
        yield 'share story' => ['/share/story', 'nothing was published'];
        yield 'contact' => ['/contact', 'tickets are not saved'];
        yield 'profile' => ['/profile', 'profile changes'];
        yield 'admin users' => ['/admin/users', 'no account was changed'];
        yield 'admin support' => ['/admin/support', 'ticket was not updated'];
    }

    #[DataProvider('previewForms')]
    public function testPreviewFormsAcknowledgeWithoutSaving(string $path, string $expectedNotice): void
    {
        $client = static::createClient();
        $client->request('POST', $path, ['_test' => '1']);

        $this->assertResponseIsSuccessful();

        $content = (string) $client->getResponse()->getContent();
        $this->assertStringContainsString('UI preview only', $content);
        $this->assertStringContainsStringIgnoringCase($expectedNotice, $content);
    }
}
