<?php

import('plugins.reports.reviewersControlReport.classes.ReviewersControlReportDAO');
import('plugins.reports.reviewersControlReport.classes.ReviewerDTO');

class ReviewersControlReport
{
    private $contextId;

    private $reportDAO;

    public function __construct(int $contextId)
    {
        $this->contextId = $contextId;
        $this->reportDAO = new ReviewersControlReportDAO();
    }

    public function assembleReport(): array
    {
        $reviewers = $this->getReviewers();
        return $reviewers;
    }

    private function getReviewers(): array
    {
        $reviewersIds = $this->reportDAO->getReviewersIds($this->contextId);
        $reviewers = array();
        foreach ($reviewersIds as $reviewerId) {
            $reviewerUser = $this->reportDAO->getReviewerUser($reviewerId);
            $reviewer = new ReviewerDTO(
                $reviewerUser->getId(),
                $reviewerUser->getEmail(),
                $reviewerUser->getFullName(),
                $reviewerUser->getLocalizedAffiliation(),
                $reviewerUser->getInterestString(),
                $this->reportDAO->getQualityAverage($reviewerId),
                $this->reportDAO->getTotalReviewedSubmissions($reviewerId),
                $this->reportDAO->getReviewedSubmissionsTitleAndDate($reviewerId)
            );
            $reviewers[] = $reviewer;
        }
        return $reviewers;
    }
}
