<?php

namespace APP\plugins\generic\reviewersControlReport\classes\traits;

use APP\facades\Repo;

trait RCRSubmissionUrl
{
    public function getSubmissionWorkflowUrl($submissionId, $submissionStageId)
    {
        $submission = Repo::submission()->get((int) $submissionId);

        return $submission ? Repo::submission()->getWorkflowUrlByUserRoles($submission) : '';
    }
}
