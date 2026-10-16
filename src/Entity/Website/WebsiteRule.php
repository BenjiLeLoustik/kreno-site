<?php

declare(strict_types=1);

namespace App\Entity\Website;

use App\Entity\AbstractBaseEntity;
use App\Repository\Website\WebsiteRuleRepository;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: WebsiteRuleRepository::class)]
class WebsiteRule extends AbstractBaseEntity
{
    #[ORM\OneToOne(WebsiteConfig::class, inversedBy: 'rule', nullable: false, onDelete: 'CASCADE')]
    private ?WebsiteConfig $website = null;

    #[ORM\Column(type: 'smallint')]
    private int $freeCancellationMinutes = 60;

    #[ORM\Column(type: 'smallint')]
    private int $bookingLeadMinutes = 15;

    #[ORM\Column(type: 'smallint')]
    private int $maxUpcomingBookingsPerUser = 2;

    #[ORM\Column(type: 'smallint')]
    private int $toleratedNoShows = 2;

    #[ORM\Column(type: 'smallint')]
    private int $noShowWindowDays = 90;

    #[ORM\Column(type: 'smallint')]
    private int $suspensionDays = 30;

    #[ORM\Column(type: 'smallint')]
    private int $minDiscount = 10;

    #[ORM\Column(type: 'smallint')]
    private int $maxDiscount = 50;

    #[ORM\Column(type: 'smallint')]
    private int $defaultDiscount = 30;

    #[ORM\Column(type: 'smallint')]
    private int $publicationWindowDays = 7;

    #[ORM\Column(type: 'smallint')]
    private int $autoRemovalMinutes = 10;

    public function getWebsite(): ?WebsiteConfig
    {
        return $this->website;
    }

    public function setWebsite(?WebsiteConfig $website): static
    {
        $this->website = $website;

        if ($website !== null && $website->getRule() !== $this) {
            $website->setRule($this);
        }

        return $this;
    }

    public function getFreeCancellationMinutes(): int
    {
        return $this->freeCancellationMinutes;
    }

    public function setFreeCancellationMinutes(int $freeCancellationMinutes): static
    {
        $this->freeCancellationMinutes = $freeCancellationMinutes;

        return $this;
    }

    public function getBookingLeadMinutes(): int
    {
        return $this->bookingLeadMinutes;
    }

    public function setBookingLeadMinutes(int $bookingLeadMinutes): static
    {
        $this->bookingLeadMinutes = $bookingLeadMinutes;

        return $this;
    }

    public function getMaxUpcomingBookingsPerUser(): int
    {
        return $this->maxUpcomingBookingsPerUser;
    }

    public function setMaxUpcomingBookingsPerUser(int $maxUpcomingBookingsPerUser): static
    {
        $this->maxUpcomingBookingsPerUser = $maxUpcomingBookingsPerUser;

        return $this;
    }

    public function getToleratedNoShows(): int
    {
        return $this->toleratedNoShows;
    }

    public function setToleratedNoShows(int $toleratedNoShows): static
    {
        $this->toleratedNoShows = $toleratedNoShows;

        return $this;
    }

    public function getNoShowWindowDays(): int
    {
        return $this->noShowWindowDays;
    }

    public function setNoShowWindowDays(int $noShowWindowDays): static
    {
        $this->noShowWindowDays = $noShowWindowDays;

        return $this;
    }

    public function getSuspensionDays(): int
    {
        return $this->suspensionDays;
    }

    public function setSuspensionDays(int $suspensionDays): static
    {
        $this->suspensionDays = $suspensionDays;

        return $this;
    }

    public function getMinDiscount(): int
    {
        return $this->minDiscount;
    }

    public function setMinDiscount(int $minDiscount): static
    {
        $this->minDiscount = $minDiscount;

        return $this;
    }

    public function getMaxDiscount(): int
    {
        return $this->maxDiscount;
    }

    public function setMaxDiscount(int $maxDiscount): static
    {
        $this->maxDiscount = $maxDiscount;

        return $this;
    }

    public function getDefaultDiscount(): int
    {
        return $this->defaultDiscount;
    }

    public function setDefaultDiscount(int $defaultDiscount): static
    {
        $this->defaultDiscount = $defaultDiscount;

        return $this;
    }

    public function getPublicationWindowDays(): int
    {
        return $this->publicationWindowDays;
    }

    public function setPublicationWindowDays(int $publicationWindowDays): static
    {
        $this->publicationWindowDays = $publicationWindowDays;

        return $this;
    }

    public function getAutoRemovalMinutes(): int
    {
        return $this->autoRemovalMinutes;
    }

    public function setAutoRemovalMinutes(int $autoRemovalMinutes): static
    {
        $this->autoRemovalMinutes = $autoRemovalMinutes;

        return $this;
    }
}