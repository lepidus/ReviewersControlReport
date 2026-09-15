Cypress.Commands.add('loginAsManager', () => {
	// A session surviving the previous test turns the login below into a no-op
	cy.logout();
	cy.login('dbarnes', null, 'publicknowledge');
	cy.location('pathname').should('include', '/dashboard');
});

Cypress.Commands.add('goToPluginsGrid', () => {
	cy.intercept('GET', '**/grid/settings/plugins/settings-plugin-grid/fetch-grid*').as('pluginsGrid');
	cy.visit('/index.php/publicknowledge/en/management/settings/website#plugins');
	cy.wait('@pluginsGrid').its('response.statusCode').should('eq', 200);
	cy.get('#plugins-button').should('be.visible').click();
	cy.waitJQuery();
});

Cypress.Commands.add('goToReviewersControlReport', () => {
	cy.visit('/index.php/publicknowledge/en/stats/reports');
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
			url: '/index.php/publicknowledge/en/stats/reports/report?pluginName=ReviewersControlReportReportPlugin',
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
