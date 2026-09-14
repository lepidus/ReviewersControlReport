Cypress.Commands.add('loginAsManager', () => {
	// A session surviving the previous test turns the login below into a no-op
	cy.logout();
	cy.login('dbarnes', null, 'publicknowledge');
	cy.location('pathname').should('include', '/submissions');
});

Cypress.Commands.add('goToPluginsGrid', () => {
	cy.visit('index.php/publicknowledge/management/settings/website');
	cy.server();
	cy.route('GET', '**/grid/plugins/plugin-grid/fetch-grid*').as('pluginsGrid');
	cy.get('#plugins-button').should('be.visible').click();
	cy.wait('@pluginsGrid').its('status').should('eq', 200);
});

Cypress.Commands.add('goToReviewersControlReport', () => {
	cy.visit('index.php/publicknowledge/stats/reports');
	cy.contains('a', 'Reviewers Control Report').click();
	cy.get('#reviewersControlReportForm').should('be.visible');
});

/**
 * Posts the report form the same way the browser would, so that the CSV can be
 * asserted on without depending on a file landing in the downloads folder.
 */
Cypress.Commands.add('requestReport', (reportType, startDate, endDate) => {
	return cy.get('input[name="csrfToken"]').invoke('val').then((csrfToken) => {
		return cy.request({
			method: 'POST',
			url: 'index.php/publicknowledge/stats/reports/report?pluginName=ReviewersControlReportReportPlugin',
			form: true,
			body: {
				csrfToken: csrfToken,
				reportType: reportType,
				startDateInterval: startDate,
				endDateInterval: endDate
			}
		});
	});
});
