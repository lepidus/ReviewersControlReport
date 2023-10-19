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
        $result = $this->reportDAO->getReviewers($this->contextId);
        $reviewers = array();
        foreach ($result as $resultReviewer) {
            $reviewer = new ReviewerDTO(
                $resultReviewer->email,
                $resultReviewer->givenName,
                $resultReviewer->familyName,
                $resultReviewer->affiliation,
                $this->getReviewerInterests($resultReviewer->user_id)
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
