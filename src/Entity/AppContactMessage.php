<?php

namespace App\Entity;

use App\Entity\User\User;
use App\Support\Enum\AppContactAuthor;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity]
class AppContactMessage extends AbstractBaseEntity
{
    #[ORM\ManyToOne(AppContact::class, inversedBy: 'messages', nullable: false, onDelete: 'CASCADE')]
    private ?AppContact $contact = null;

    #[ORM\Column]
    private ?AppContactAuthor $authorType = null;

    #[ORM\ManyToOne(User::class, onDelete: 'SET NULL')]
    private ?User $author = null;

    #[ORM\Column(type: 'text')]
    private ?string $body = null;

    public function getContact(): ?AppContact
    {
        return $this->contact;
    }

    public function setContact(?AppContact $contact): static
    {
        $this->contact = $contact;

        return $this;
    }

    public function getAuthorType(): ?AppContactAuthor
    {
        return $this->authorType;
    }

    public function setAuthorType(AppContactAuthor $authorType): static
    {
        $this->authorType = $authorType;

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(string $body): static
    {
        $this->body = $body;

        return $this;
    }
}