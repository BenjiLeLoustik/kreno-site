<?php

namespace App\Review\Service;

use App\Company\Enum\MemberStatus;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Company\CompanyMember;
use App\Entity\Establishment\EstablishmentReview;
use App\Entity\User\User;
use App\Establishment\Enum\EstablishmentRole;
use App\Review\Enum\ReviewText;
use App\User\Enum\NotificationType;
use App\User\Service\NotificationService;
use NeoPHP\Component\Routing\RoutingManagerInterface;

class ReviewNotifier
{
    public function __construct(
        private NotificationService $notifications,
        private RoutingManagerInterface $routing,
    ) {
    }

    public function received(EstablishmentReview $review): void
    {
        $title = ReviewText::RECEIVED_TITLE->text($review->getRating(), $this->name($review));

        $this->notifyProfessionals($review, $title, $review->getComment());
    }

    public function replied(EstablishmentReview $review): void
    {
        $title = ReviewText::REPLIED_TITLE->text($this->name($review));

        $this->notifyCustomer($review, $title, $review->getReply());
    }

    public function hidden(EstablishmentReview $review, bool $reported): void
    {
        $title = ReviewText::HIDDEN_TITLE->text($this->name($review));
        $this->notifyCustomer($review, $title, $review->getModerationNote());

        if ($reported) {
            $title = ReviewText::REPORT_ACCEPTED_TITLE->text($this->name($review));

            $this->notifyProfessionals($review, $title, $review->getModerationNote());
        }
    }

    public function kept(EstablishmentReview $review): void
    {
        $title = ReviewText::REPORT_REJECTED_TITLE->text($this->name($review));

        $this->notifyProfessionals($review, $title, $review->getComment());
    }

    private function notifyCustomer(EstablishmentReview $review, string $title, ?string $body): void
    {
        $customer = $review->getCustomer();

        if ($customer === null) {
            return;
        }

        $link = $this->routing->generate('account_reviews');
        $this->notifications->notify($customer, NotificationType::REVIEW, $title, $body, $link, false);
    }

    private function notifyProfessionals(EstablishmentReview $review, string $title, ?string $body): void
    {
        $link = $this->routing->generate('pro_reviews');

        foreach ($this->professionals($review->getEstablishment()) as $user) {
            $this->notifications->notify($user, NotificationType::REVIEW, $title, $body, $link, false);
        }
    }

    private function professionals(?CompanyEstablishment $establishment): array
    {
        $users = [];

        foreach ($establishment?->getCompany()?->getMembers() ?? [] as $member) {
            if ($this->isReachable($member) && $member->getRole() !== null) {
                $users[spl_object_id($member->getUser())] = $member->getUser();
            }
        }

        foreach ($establishment?->getMembers() ?? [] as $staff) {
            $member = $staff->getMember();

            if ($this->isManager($member, $staff->getRole())) {
                $users[spl_object_id($member->getUser())] = $member->getUser();
            }
        }

        return array_values($users);
    }

    private function isManager(?CompanyMember $member, ?EstablishmentRole $role): bool
    {
        if ($member === null || !$this->isReachable($member)) {
            return false;
        }

        return $role === EstablishmentRole::MANAGER;
    }

    private function isReachable(CompanyMember $member): bool
    {
        return $member->getStatus() === MemberStatus::ACTIVE && $member->getUser() instanceof User;
    }

    private function name(EstablishmentReview $review): string
    {
        return (string) $review->getEstablishment()?->getName();
    }
}