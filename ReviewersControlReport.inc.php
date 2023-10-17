<?php

require_once('ReviewersControlReportDAO.inc.php');

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
        $report = $this->reportDAO->getReviewers($this->contextId);
        return $report;
    }
}
