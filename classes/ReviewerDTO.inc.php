<?php

import('lib.pkp.classes.linkAction.request.AjaxModal');
import('plugins.reports.reviewersControlReport.classes.traits.StringLength');
import('plugins.reports.reviewersControlReport.classes.traits.ReviewerGridHandlerLinkAction');

class ReviewerDTO
{
    use StringLength;
    use ReviewerGridHandlerLinkAction;
    private $id;
    private $fullName;
    private $email;
    private $affiliation;
    private $interests;
    private $qualityAverage;
    private $totalReviewedSubmissions;
    private $reviewedSubmissionsTitleAndDate;

    public function __construct($id, $email, $fullName, $affiliation, $interests, $qualityAverage, $totalReviewedSubmissions, $reviewedSubmissionsTitleAndDate)
    {
        $this->id = (int) $id;
        $this->fullName = $this->formatStringLength($fullName);
        $this->email = $this->formatStringLength($email);
        $this->affiliation = $this->formatStringLength((string)$affiliation);
        $this->interests = $this->formatStringLength($interests);
        $this->qualityAverage = $qualityAverage;
        $this->totalReviewedSubmissions = $totalReviewedSubmissions;
        $this->reviewedSubmissionsTitleAndDate = $reviewedSubmissionsTitleAndDate;
    }

    private function getId(): int
    {
        return $this->id;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getAffiliation()
    {
        return $this->affiliation;
    }

    public function getInterests(): string
    {
        return $this->interests;
    }

    public function getQualityAverage(): float
    {
        return number_format($this->qualityAverage, 2, '.', '');
    }

    public function getTotalReviewedSubmissions(): int
    {
        return $this->totalReviewedSubmissions;
    }

    public function getReviewedSubmissionsTitleAndDate(): array
    {
        return $this->reviewedSubmissionsTitleAndDate;
    }
}
