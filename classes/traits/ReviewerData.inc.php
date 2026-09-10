<?php

trait ReviewerData
{
    public function getReviewersPersonalData(array $reviewerIds): array
    {
        $reviewersPersonalData = [];
        foreach ($reviewerIds as $reviewerId) {
            $reviewersPersonalData[$reviewerId] = $this->getReviewerPersonalData($reviewerId);
        }

        return $reviewersPersonalData;
    }

    /**
     * The reviewers a set of reviews belongs to, which is not the same as the
     * journal's current reviewers: a review stays on record after its reviewer
     * loses the role, and its row would otherwise carry no name at all.
     */
    public function getReviewersPersonalDataOfReviews(array $completedReviews): array
    {
        $reviewerIds = [];
        foreach ($completedReviews as $completedReview) {
            $reviewerIds[] = $completedReview->getReviewerId();
        }

        return $this->getReviewersPersonalData(array_unique($reviewerIds));
    }

    public function getReviewerPersonalData($reviewerId): array
    {
        $userDao = DAORegistry::getDAO('UserDAO');
        $reviewer = $userDao->getById($reviewerId);

        if (is_null($reviewer)) {
            return ['', '', '', ''];
        }

        return [
            $reviewer->getLocalizedGivenName() . ' ' . $reviewer->getLocalizedFamilyName(),
            $reviewer->getEmail(),
            $reviewer->getLocalizedAffiliation(),
            $reviewer->getInterestString()
        ];
    }
}
