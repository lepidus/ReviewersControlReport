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

    public function getReviewerPersonalData($reviewerId): array
    {
        $userDao = DAORegistry::getDAO('UserDAO');
        $reviewer = $userDao->getById($reviewerId);

        return [
            $reviewer->getLocalizedGivenName() . ' ' . $reviewer->getLocalizedFamilyName(),
            $reviewer->getEmail(),
            $reviewer->getLocalizedAffiliation(),
            $reviewer->getInterestString()
        ];
    }
}
