{**
 * plugins/reports/counter/templates/index.tpl
 *
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 *}
{extends file="layouts/backend.tpl"}

{block name="page"}
    <h1 class="app__pageHeading">
        {translate key="plugins.reports.reviewersControlReport.displayName"}
    </h1>

    <div class="app__contentPanel">
        <p>{translate key="plugins.reports.reviewersControlReport.description"}</p>
        {if $report}
            <table>
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Full Name</th>
                        <th>Affiliation</th>
                        <th>Interests</th>
                        <th>Quality Average</th>
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
                            <td>{$reviewer->getReviewedSubmissions()}</td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        {else}
            <p>No reviewers found.</p>
        {/if}
    </div>
{/block}