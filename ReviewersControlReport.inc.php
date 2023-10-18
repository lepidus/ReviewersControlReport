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
                $resultReviewer->affiliation
            );
            $reviewers[] = $reviewer;
        }
        return $reviewers;
    }
}
