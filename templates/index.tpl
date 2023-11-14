{**
 * plugins/reports/reviewersControlReport/templates/index.tpl
 *
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *}

 {extends file="layouts/backend.tpl"}

 {block name="page"}
     <div class="pkpStats">
         <div class="pkpStats__panel" style="width: fit-content;">
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
                             <th>{translate key="plugins.reports.reviewersControlReport.field.qualityAverage"}</th>
                             <th>{translate key="plugins.reports.reviewersControlReport.field.reviewedSubmissionsTotal"}</th>
                             <th>{translate key="plugins.reports.reviewersControlReport.field.reviewedSubmissionsTitleAndCompletedDate"}</th>
                             <th>{translate key="grid.user.edit"}</th>
                         </tr>
                     </thead>
                     <tbody>
                         {foreach from=$report item=reviewer}
                             <tr>
                                 <td>{$reviewer->getEmail()}</td>
                                 <td>{$reviewer->getFullName()}</td>
                                 <td>{$reviewer->getAffiliation()}</td>
                                 <td>{$reviewer->getInterests()}</td>
                                 <td>
                                    {if $reviewer->getQualityAverage() > 0}
                                        {$reviewer->getQualityAverage()}
                                    {else}
                                        ---
                                    {/if}
                                    </td>
                                 <td>{$reviewer->getTotalReviewedSubmissions()}</td>
                                 <td>
                                    <button class="pkpButton expand-button">
                                        <span class="button-text">▼</span>
                                    </button>
                                </td>
                                 <td><p class="pkpButton">{include file="linkAction/linkAction.tpl" action=$reviewer->getEditUserReviewerLinkAction()}</p></td>
                             </tr>
                             <tr class="expand-content">
                                 <td class="reviewedSubmissionsDescription">
                                    {if $reviewer->getReviewedSubmissionsTitleAndDate() == []}
                                        <p style="font-weight: 700;font-size: .75rem;">{translate key="plugins.reports.reviewersControlReport.field.reviewedSubmissionsTitleAndCompletedDate.empty"}</p>
                                    {else}
                                        <div class="pkpStats__panel reviewedSubmissionsPanel">
                                            <table class="pkpTable reviewedSubmissionsTable">
                                                <tbody>
                                                    {foreach from=$reviewer->getReviewedSubmissionsTitleAndDate() item=submission}
                                                        <tr>
                                                            <td><a href="{$submission[2]}">{$submission[0]}</a></td>
                                                            <td>{$submission[1]}</td>
                                                        </tr>
                                                    {/foreach}
                                                </tbody>
                                            </table>
                                        </div>
                                    {/if}
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
 