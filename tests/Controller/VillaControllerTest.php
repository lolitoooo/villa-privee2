<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\Villa;
use App\Repository\UserRepository;
use App\Repository\VillaRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class VillaControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $testVilla;
    private $testOwner;
    private $normalUser;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()
            ->get('doctrine')
            ->getManager();
            
        // Nettoyer la base de données de test
        $this->entityManager->createQuery('DELETE FROM App\\Entity\\Villa')->execute();
        $this->entityManager->createQuery('DELETE FROM App\\Entity\\User')->execute();

        // Créer les utilisateurs de test
        $this->testOwner = new User();
        $this->testOwner->setEmail('owner@example.com');
        $this->testOwner->setPassword('$2y$13$hK6jkbu1hBSq5NU76aQHJei4RlqWie2xXGLjUf8S3B2AYSvwjzQ1O');
        $this->testOwner->setRoles(['ROLE_OWNER']);
        $this->testOwner->setFirstName('Test');
        $this->testOwner->setLastName('Owner');
        
        $this->normalUser = new User();
        $this->normalUser->setEmail('user@example.com');
        $this->normalUser->setPassword('$2y$13$hK6jkbu1hBSq5NU76aQHJei4RlqWie2xXGLjUf8S3B2AYSvwjzQ1O');
        $this->normalUser->setRoles(['ROLE_USER']);
        $this->normalUser->setFirstName('Test');
        $this->normalUser->setLastName('User');

        $this->entityManager->persist($this->testOwner);
        $this->entityManager->persist($this->normalUser);
        $this->entityManager->flush();

        // Créer une villa de test
        $this->testVilla = new Villa();
        $this->testVilla->setTitle('Villa Test');
        $this->testVilla->setDescription('Description test');
        $this->testVilla->setPrice(1000);
        $this->testVilla->setCapacity(4);
        $this->testVilla->setBedrooms(2);
        $this->testVilla->setBathrooms(1);
        $this->testVilla->setLocation('Test Location');
        $this->testVilla->setOwner($this->testOwner);
        $this->testVilla->setIsActive(true);
        $this->testVilla->setSlug('villa-test');

        $this->entityManager->persist($this->testVilla);
        $this->entityManager->flush();
    }

    public function testShowVillaAsAnonymous(): void
    {
        $this->client->request('GET', '/villas/'.$this->testVilla->getId());
        $this->assertResponseRedirects('/login');
    }

    public function testShowVillaAsUser(): void
    {
        $this->client->loginUser($this->normalUser);
        $this->client->request('GET', '/villas/'.$this->testVilla->getId());
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Villa Test');
        $this->assertSelectorNotExists('a[href$="/modifier"]');
    }

    public function testShowVillaAsOwner(): void
    {
        $this->client->loginUser($this->testOwner);
        $this->client->request('GET', '/villas/'.$this->testVilla->getId());
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Villa Test');
        $this->assertSelectorExists('a[href$="/modifier"]');
    }

    protected function tearDown(): void
    {
        // Nettoyer la base de données
        if ($this->entityManager) {
            $this->entityManager->createQuery('DELETE FROM App\\Entity\\Villa')->execute();
            $this->entityManager->createQuery('DELETE FROM App\\Entity\\User')->execute();
            $this->entityManager->close();
        }

        $this->entityManager = null;
        parent::tearDown();
    }
}
