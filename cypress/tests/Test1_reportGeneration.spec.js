import '../support/commands';

// The CSV is asserted as one whole string, never split or sliced: the plugin
// has no node_modules, so any array/string method Babel wants to polyfill
// (push, trim, match, padStart) breaks the bundling of the entire spec before
// a single test runs.
//
// The report is requested rather than downloaded by clicking: the Cypress 5.6
// that OJS 3.3 pins has no downloads folder, so a file saved by the browser
// cannot be read back.
describe('Reviewers Control Report - Report generation', function() {
	const reviewsHeader = '"Submission ID","Submission Title","Review Round",Reviewer,Email,Affiliation,"Date Assigned","Date Due","Date Completed",Recommendation,"Quality Rating"';
	const reviewersHeader = '"Quality Average","Completed Reviews","Reviewed Submissions (Titles)"';
	const reviewedSubmission = 'Developing efficacy beliefs in the classroom';

	beforeEach(function() {
		cy.loginAsManager();
		cy.goToReviewersControlReport();
	});

	it('Offers the report type and the review completion period', function() {
		cy.contains('h2', 'Generate CSV Report');
		cy.contains('legend', 'Report Type');
		cy.get('#reportType').within(() => {
			cy.contains('option', 'Report by reviewers (one row per reviewer)');
			cy.contains('option', 'Report by reviews (one row per review)');
		});

		cy.contains('legend', 'Review completion date range');
		cy.get('#startDateInterval').should('have.attr', 'type', 'date');
		cy.get('#endDateInterval').should('have.attr', 'type', 'date');

		cy.contains('h2', 'Reviewers');
		cy.get('#reviewersReportGridContainer').should('be.visible');
	});

	it('Generates the reviewers report with no date left inside the titles cell', function() {
		cy.get('#reportType').select('Report by reviewers (one row per reviewer)');
		cy.get('#reportType').should('have.value', 'reviewers');

		cy.requestReport('reviewers', '', '').then((response) => {
			expect(response.headers['content-type']).to.contain('text/comma-separated-values');
			expect(response.headers['content-disposition']).to.contain('reviewersControlReport-');
			expect(response.body).to.contain(reviewersHeader);
			// The completion date now has a column of its own, instead of
			// riding along inside the submission titles cell
			expect(response.body).not.to.contain('Completed:');
			expect(response.body).to.contain(reviewedSubmission);
		});
	});

	it('Generates the reviews report as a separate file', function() {
		cy.get('#reportType').select('Report by reviews (one row per review)');
		// The option label is what the user picks; the value is what the server reads
		cy.get('#reportType').should('have.value', 'reviews');

		cy.requestReport('reviews', '', '').then((response) => {
			expect(response.headers['content-type']).to.contain('text/comma-separated-values');
			expect(response.headers['content-disposition']).to.contain('reviewsControlReport-');
			expect(response.body).to.contain(reviewsHeader);
			expect(response.body).to.contain(reviewedSubmission);
		});
	});

	it('Gives each review a row whose dates sit in columns of their own', function() {
		cy.requestReport('reviews', '', '').then((response) => {
			// Date assigned, date due and date completed, one plain date each
			cy.wrap(response.body)
				.should('match', /\n[^\n]*,\d{4}-\d{2}-\d{2},\d{4}-\d{2}-\d{2},\d{4}-\d{2}-\d{2},[^,\n]*,[^,\n]*/);
		});
	});

	it('Leaves out reviews completed outside the chosen period', function() {
		cy.requestReport('reviews', '2000-01-01', '2000-12-31').then((response) => {
			expect(response.body).to.contain(reviewsHeader);
			expect(response.body).not.to.contain(reviewedSubmission);
		});
	});

	it('Keeps every reviewer listed when the period has no reviews, with empty totals', function() {
		cy.requestReport('reviewers', '2000-01-01', '2000-12-31').then((response) => {
			expect(response.body).to.contain(reviewersHeader);
			cy.wrap(response.body).should('match', /Paul Hudson[^\n]*,,,,\n/);
		});
	});

	it('Refuses a period that ends before it starts', function() {
		cy.get('#startDateInterval').type('2026-12-31');
		cy.get('#endDateInterval').type('2026-01-01');
		cy.get('#generateReport').click();

		cy.contains('The start date must not be later than the end date.').should('be.visible');
		cy.get('#reviewersControlReportForm').should('be.visible');
	});
});
