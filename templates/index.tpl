{**
* plugins/reports/reviewersControlReport/templates/index.tpl
*
* Distributed under the GNU GPL v3. For full terms see the file LICENSE.
*
*}

{extends file="layouts/backend.tpl"}

{block name="page"}
    <div class="pkpStats">
        <div class="pkpStats__panel" style="width: 800pt;">
            <pkp-header>
                <h1 id="usersTableLabel" class="pkpHeader__title">
                    {translate key="plugins.reports.reviewersControlReport.displayName"}</h1>
            </pkp-header>
            {if $report}
                <table class="pkpTable table-sortable" labelled-by="usersTableLabel">
                    <thead>
                        <tr>
                            <th>{translate key="plugins.reports.reviewersControlReport.field.email"}</th>
                            <th>{translate key="plugins.reports.reviewersControlReport.field.fullName"}</th>
                            <th>{translate key="plugins.reports.reviewersControlReport.field.affiliation"}</th>
                            <th>{translate key="plugins.reports.reviewersControlReport.field.interests"}</th>
                            <th>{translate key="grid.user.edit"}</th>
                            <th>Expandir</th>
                        </tr>
                    </thead>
                    <tbody>
                    {foreach from=$report item=reviewer}
                        <tr>
                            <td>{$reviewer->getEmail()}</td>
                            <td>{$reviewer->getFullName()}</td>
                            <td>{$reviewer->getAffiliation()}</td>
                            <td>{$reviewer->getInterests()}</td>
                            <td><p class="pkpButton">{include file="linkAction/linkAction.tpl" action=$reviewer->getEditUserReviewerLinkAction()}</p></td>
                            <td>
                                <button class="expand-button">Expandir</button>
                                <div class="expand-content">
                                    <table>
                                        <tr>
                                            <th>{translate key="plugins.reports.reviewersControlReport.field.qualityAverage"}</th>
                                            <th>{translate key="plugins.reports.reviewersControlReport.field.reviewedSubmissionsTotal"}</th>
                                            <th>{translate key="plugins.reports.reviewersControlReport.field.reviewedSubmissionsTitleAndCompletedDate"}</th>
                                        </tr>
                                        <tr>
                                            <td>{if $reviewer->getQualityAverage() > 0}
                                                    {$reviewer->getQualityAverage()}
                                                {else}
                                                    ---
                                                {/if}
                                            </td>
                                            <td>{$reviewer->getTotalReviewedSubmissions()}</td>
                                            {if $reviewer->getReviewedSubmissionsTitleAndDate() == []}
                                                <td>{translate key="plugins.reports.reviewersControlReport.field.reviewedSubmissionsTitleAndCompletedDate.empty"}</td>
                                            {else}
                                                <td>
                                                    <ul>
                                                        {foreach from=$reviewer->getReviewedSubmissionsTitleAndDate() item=submission}
                                                            <li><a href="{$submission[2]}">{$submission[0]}</a>
                                                                <ul>
                                                                    <li style="color: grey; font-style: italic;">{$submission[1]}</li>
                                                                </ul>
                                                            </li>
                                                        {/foreach}
                                                    </ul>
                                                </td>
                                            {/if}
                                        </tr>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    {/foreach}
                    
                    </tbody>
                </table>
            {else}
                <p>{translate key="plugins.reports.reviewersControlReport.NoReviewers"}</p>
            {/if}
        </div>
    </div>
{/block}