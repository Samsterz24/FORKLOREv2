<?php

namespace App\Tests\Repository;

use App\Entity\Recipe;
use App\Entity\User;
use App\Repository\RecipeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RecipeRepositoryTest extends KernelTestCase
{
    public function testFindAllReturnsArray(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $repo = $container->get(RecipeRepository::class);

        $user = new User();
        $user->setUsername('tester');
        $user->setEmail('tester@example.com');
        $user->setPassword('secret');
        $em->persist($user);

        $recipe = new Recipe();
        $recipe->setTitle('Test Recipe');
        $recipe->setUser($user);
        $em->persist($recipe);
        $em->flush();

        $this->assertIsArray($repo->findAll());

        $em->remove($recipe);
        $em->remove($user);
        $em->flush();
    }
}