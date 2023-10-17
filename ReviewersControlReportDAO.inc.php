<?php

import('lib.pkp.classes.db.DAO');

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Collection;

class ReviewersControlReportDAO extends DAO
{
    public function getReviewers($journalId)
    {
        $resultGroup = Capsule::table('user_groups')
        ->select('user_group_id')
        ->where('role_id', ROLE_ID_REVIEWER)
        ->where('context_id', $journalId)
        ->first();
        $userGroupId = get_object_vars($resultGroup)['user_group_id'];

        $query = Capsule::table('users AS u')
        ->select('u.email')
        ->whereIn('u.user_id', function ($query) use ($userGroupId) {
            $query->select('user_id')
            ->from('user_user_groups')
            ->where('user_group_id', $userGroupId);
        });

        return $query->get();
    }
}
