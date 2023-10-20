<?php

class ReviewerDTO
{
    private $fullName;
    private $email;
    private $affiliation;
    private $interests;
    private $qualityAverage;
    private $totalReviewedSubmissions;
    private $reviewedSubmissionsTitleAndDate;

    public function __construct($email, $fullName, $affiliation, $interests, $qualityAverage, $totalReviewedSubmissions, $reviewedSubmissionsTitleAndDate)
    {
        $this->fullName = $fullName;
        $this->email = $email;
        $this->affiliation = $affiliation;
        $this->interests = $interests;
        $this->qualityAverage = $qualityAverage;
        $this->totalReviewedSubmissions = $totalReviewedSubmissions;
        $this->reviewedSubmissionsTitleAndDate = $reviewedSubmissionsTitleAndDate;
    }

    public function getFullName()
    {
        return $this->fullName;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getAffiliation()
    {
        return $this->affiliation;
    }

    public function getInterests()
    {
        return $this->interests;
    }

    public function getQualityAverage()
    {
        return number_format($this->qualityAverage, 2, '.', '');
    }

    public function getTotalReviewedSubmissions()
    {
        return $this->totalReviewedSubmissions;
    }

    public function getReviewedSubmissionsTitleAndDate()
    {
        $output = "";
        foreach ($this->reviewedSubmissionsTitleAndDate as $reviewedSubmission) {
            $output .= "<p>" . $reviewedSubmission[0] . ", " . $reviewedSubmission[1] . "</p>";
        }
        return $output;
    }
}
