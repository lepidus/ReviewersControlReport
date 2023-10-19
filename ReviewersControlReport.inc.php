<?php

require_once('ReviewersControlReportDAO.inc.php');
require_once('ReviewerDTO.inc.php');

class ReviewersControlReport
{
    private int $contextId;

    private ReviewersControlReportDAO $reportDAO;

    public function __construct($request)
    {
        $this->contextId = $request->getContext() ? $request->getContext()->getId() : CONTEXT_SITE;
        $this->reportDAO = new ReviewersControlReportDAO();
    }

    public function assembleReport()
    {
        $reviewers = $this->getReviewers();
        return $reviewers;
    }

    private function getReviewers()
    {
        $reviewersIds = $this->reportDAO->getReviewersIds($this->contextId);
        $reviewers = array();
        foreach ($reviewersIds as $reviewerId) {
            $reviewerUser = $this->reportDAO->getReviewerUser($reviewerId);
            $reviewer = new ReviewerDTO(
                $reviewerUser->getEmail(),
                $reviewerUser->getFullName(),
                $reviewerUser->getLocalizedAffiliation(),
                $reviewerUser->getInterestString(),
                $this->reportDAO->getQualityAverage($reviewerId),
                $this->reportDAO->getReviewedSubmissionsTitleAndDate($reviewerId)
            );
            $reviewers[] = $reviewer;
        }
        return $reviewers;
    }

    private function getReviewerInterests($userId)
    {
        $reviewerInterestsResult = $this->reportDAO->getUserInterestsById($userId);
        $interests = array();
        foreach ($reviewerInterestsResult as $interest) {
            $interests[] = get_object_vars($interest)['interest'];
        }
        return $interests;
    }
}
