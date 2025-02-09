<?php

namespace App\Tests\Security\Voter;

use App\Entity\User;
use App\Entity\Villa;
use App\Security\Voter\VillaVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class VillaVoterTest extends TestCase
{
    private VillaVoter $voter;
    private Security $security;
    private TokenInterface $token;

    protected function setUp(): void
    {
        $this->security = $this->createMock(Security::class);
        $this->token = $this->createMock(TokenInterface::class);
        $this->voter = new VillaVoter($this->security);
    }

    public function testVoteOnAttributeForAnonymousUser(): void
    {
        // Configurer le mock pour un utilisateur non connecté
        $this->token->expects($this->any())
            ->method('getUser')
            ->willReturn(null);

        // L'accès devrait être refusé pour toutes les actions
        $villa = new Villa();
        
        $this->assertEquals(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->token, $villa, [VillaVoter::VIEW])
        );
    }

    public function testVoteOnAttributeForNormalUser(): void
    {
        // Créer un utilisateur normal
        $user = new User();
        $user->setRoles(['ROLE_USER']);

        $villa = new Villa();
        
        // Configurer les mocks
        $this->token->expects($this->any())
            ->method('getUser')
            ->willReturn($user);

        $this->security->expects($this->any())
            ->method('isGranted')
            ->willReturnMap([
                ['ROLE_ADMIN', null, false],
                ['ROLE_USER', null, true],
                ['ROLE_OWNER', null, false]
            ]);

        // Peut voir mais ne peut pas éditer
        $this->assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($this->token, $villa, [VillaVoter::VIEW])
        );

        $this->assertEquals(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->token, $villa, [VillaVoter::EDIT])
        );
    }

    public function testVoteOnAttributeForOwner(): void
    {
        // Créer un propriétaire et une villa
        $owner = new User();
        $owner->setRoles(['ROLE_OWNER']);
        
        $villa = new Villa();
        $villa->setOwner($owner);

        // Configurer les mocks
        $this->token->expects($this->any())
            ->method('getUser')
            ->willReturn($owner);

        $this->security->expects($this->any())
            ->method('isGranted')
            ->willReturnMap([
                ['ROLE_ADMIN', null, false],
                ['ROLE_USER', null, true],
                ['ROLE_OWNER', null, true]
            ]);

        // Le propriétaire peut tout faire sauf ajouter des avis
        $this->assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($this->token, $villa, [VillaVoter::VIEW])
        );

        $this->assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($this->token, $villa, [VillaVoter::EDIT])
        );

        $this->assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($this->token, $villa, [VillaVoter::DELETE])
        );

        $this->assertEquals(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->token, $villa, [VillaVoter::ADD_REVIEW])
        );
    }
}
