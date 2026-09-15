<?php

namespace APP\plugins\generic\reviewersControlReport\classes\traits;

use APP\core\Application;

trait RCRSubmissionUrl
{
    /**
     * Only editorial roles see the grid, so every link goes to the editorial
     * workflow, which enforces its own access. Resolving the URL by the roles
     * of the user would cost several queries per listed review.
     */
    public function getSubmissionWorkflowUrl($submissionId, $submissionStageId)
    {
        $request = Application::get()->getRequest();
        $dispatcher = $request->getDispatcher();
        if (!$dispatcher) {
            return '';
        }

        return $dispatcher->url($request, Application::ROUTE_PAGE, null, 'workflow', 'access', [(int) $submissionId]);
    }
}
