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
            <reviewers-list-panel
                v-bind="components.reviewers"
                @set="set"
            >
                <template v-slot:reviews="{ldelim}item{rdelim}">
                    <div class="listPanel__item--reviewer__detailHeading">
                        {translate key="plugins.reports.reviewersControlReport.list.reports"}
                    </div>
                    <ul class="list">
                        {foreach from=$report item=reviewer}
                            {foreach from=$reviewer->getReviewedSubmissionsTitleAndDate() item=submission}
                                <li class="listItem">
                                    <p>{$submission[0]}, <span style="color: grey; font-style: italic;">{$submission[1]}</span></p>
                                </li>
                            {/foreach}
                        {/foreach}
                    </ul>
                </template>
            </reviewers-list-panel>
        </div> 
    </div>

{/block}