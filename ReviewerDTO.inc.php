<?php

class ReviewerDTO
{
    private $fullName;
    private $email;
    private $affiliation;
    private $interests;
    private $qualityAverage;
    private $reviewedSubmissions;

    public function __construct($email, $fullName, $affiliation, $interests, $qualityAverage, $reviewedSubmissions)
    {
        $this->fullName = $fullName;
        $this->email = $email;
        $this->affiliation = $affiliation;
        $this->interests = $interests;
        $this->qualityAverage = $qualityAverage;
        $this->reviewedSubmissions = $reviewedSubmissions;
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

    public function getReviewedSubmissions()
    {
        $output = "";
        foreach($this->reviewedSubmissions as $reviewedSubmission) {
            $output .= "<p>" . $reviewedSubmission[0] . ", " . date("Y-m-d", strtotime($reviewedSubmission[1])) . "</p>";
        }
        return $output;
    }
}
