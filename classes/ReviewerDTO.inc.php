<?php

import('lib.pkp.classes.linkAction.request.AjaxModal');
import('plugins.generic.reviewersControlReport.classes.traits.StringLength');

class ReviewerDTO extends DataObject
{
    use StringLength;

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
        $this->setData('id', $id);
        $this->fullName = $this->formatStringLength($fullName, 16);
        $this->email = $this->formatStringLength($email, 25);
        $this->affiliation = $this->formatStringLength((string)$affiliation);
        $this->interests = $this->formatStringLength($interests);
        $this->qualityAverage = $qualityAverage;
        $this->totalReviewedSubmissions = $totalReviewedSubmissions;
        $this->reviewedSubmissionsTitleAndDate = $reviewedSubmissionsTitleAndDate;
    }

    public function getId(): int
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

    public function getQualityAverage()
    {
        if ($this->qualityAverage > 0) {
            return number_format($this->qualityAverage, 0, '.', '');
        }
        return "---";
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
