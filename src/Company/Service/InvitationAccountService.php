<?php

namespace App\Company\Service;

use App\Company\Dto\AcceptInvitationInput;
use App\Company\Exception\InvitationAccountExistsException;
use App\Company\Exception\InvitationDetailsRequiredException;
use App\Entity\User\User;
use App\Repository\User\UserRepository;
use App\User\Enum\UserRole;
use DateTimeImmutable;
use NeoPHP\Package\Orm\OrmManagerInterface;
use NeoPHP\Package\Security\Hasher\UserPasswordHasher;

class InvitationAccountService
{
    public function __construct(
        private TeamService $team,
        private UserRepository $users,
        private UserPasswordHasher $hasher,
        private OrmManagerInterface $orm,
    ) {
    }

    public function create(string $token, AcceptInvitationInput $input): User
    {
        $email = (string) $this->team->findInvitation($token)->getEmail();
        $this->assertCreatable($email, $input);

        $user = (new User())
            ->setEmail($email)
            ->setFirstName((string) $input->firstName)
            ->setLastName($input->lastName)
            ->setRoles([UserRole::USER->value])
            ->setEmailVerifiedAt(new DateTimeImmutable());

        $user->setPassword($this->hasher->hashPassword($user, (string) $input->password));
        $this->orm->persist($user);
        $this->orm->flush();

        return $user;
    }

    private function assertCreatable(string $email, AcceptInvitationInput $input): void
    {
        if ($this->users->emailExists($email)) {
            throw new InvitationAccountExistsException($email);
        }

        if ($input->firstName === null || $input->password === null) {
            throw new InvitationDetailsRequiredException();
        }
    }
}