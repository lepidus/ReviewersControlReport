{**
 * plugins/reports/reviewersControlReport/templates/index.tpl
 *
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 *}

{extends file="layouts/backend.tpl"}

{block name="page"}
    <div class="pkpStats">
        <div class="pkpStats__panel">
            <pkp-header>
                <h1 id="usersTableLabel" class="pkpHeader__title">
                    {translate key="plugins.reports.reviewersControlReport.displayName"}</h1>
            </pkp-header>
            {if $report}
                <table class="pkpTable" labelled-by="usersTableLabel">
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Full Name</th>
                            <th>Affiliation</th>
                            <th>Interests</th>
                            <th>Quality Average</th>
                            <th>Reviewed Submissions(Total)</th>
                            <th>Reviewed Submissions(Title and Completed Date)</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$report item=reviewer}
                            <tr>
                                <td>{$reviewer->getEmail()}</td>
                                <td>{$reviewer->getFullName()}</td>
                                <td>{$reviewer->getAffiliation()}</td>
                                <td>{$reviewer->getInterests()}</td>
                                <td>{$reviewer->getQualityAverage()}</td>
                                <td>{$reviewer->getTotalReviewedSubmissions()}</td>
                                {if $reviewer->getReviewedSubmissionsTitleAndDate() == []}
                                    <td>No reviewed submissions</td>
                                {else}
                                    <td>
                                        <ul>
                                            {foreach from=$reviewer->getReviewedSubmissionsTitleAndDate() item=submission}
                                                <li>{$submission[0]}
                                                    <ul>
                                                        <li style="color: grey; font-style: italic;">{$submission[1]}</li>
                                                    </ul>
                                                </li>
                                            {/foreach}
                                        </ul>
                                    </td>
                                {/if}
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            {else}
                <p>No reviewers found.</p>
            {/if}
        </div>
    </div>
{/block}