<?php

namespace APP\plugins\generic\reviewersControlReport\classes\traits;

use APP\facades\Repo;
use PKP\facades\Locale;

trait RCRReviewerData
{
    public function getReviewersPersonalData(array $reviewerIds, ?string $locale = null): array
    {
        $reviewersPersonalData = [];
        foreach ($reviewerIds as $reviewerId) {
            $reviewersPersonalData[$reviewerId] = $this->getReviewerPersonalData($reviewerId, $locale);
        }

        return $reviewersPersonalData;
    }

    /**
     * The reviewers a set of reviews belongs to, which is not the same as the
     * journal's current reviewers: a review stays on record after its reviewer
     * loses the role, and its row would otherwise carry no name at all.
     */
    public function getReviewersPersonalDataOfReviews(array $completedReviews, ?string $locale = null): array
    {
        $reviewerIds = [];
        foreach ($completedReviews as $completedReview) {
            $reviewerIds[] = $completedReview->getReviewerId();
        }

        return $this->getReviewersPersonalData(array_unique($reviewerIds), $locale);
    }

    public function getReviewerPersonalData($reviewerId, ?string $locale = null): array
    {
        $reviewer = Repo::user()->get((int) $reviewerId);

        if (is_null($reviewer)) {
            return ['', '', '', ''];
        }

        return [
            trim(
                $this->getLocalizedUserData($reviewer, 'givenName', $locale) . ' '
                . $this->getLocalizedUserData($reviewer, 'familyName', $locale)
            ),
            $reviewer->getEmail(),
            $this->getLocalizedUserData($reviewer, 'affiliation', $locale),
            $reviewer->getInterestString()
        ];
    }

    private function getLocalizedUserData($reviewer, string $field, ?string $locale = null): string
    {
        $values = (array) $reviewer->getData($field);
        $locale = $locale ?? Locale::getLocale();
        return (string) ($values[$locale] ?? reset($values) ?: '');
    }
}
