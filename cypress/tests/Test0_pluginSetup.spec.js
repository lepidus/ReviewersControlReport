import '../support/commands';

describe('Reviewers Control Report - Plugin enabling', function() {
	it('Enables the plugin', function() {
		cy.loginAsManager();
		cy.goToPluginsGrid();

		cy.get('input[id^=select-cell-ReviewersControlReportPlugin]').check();
		cy.get('input[id^=select-cell-ReviewersControlReportPlugin]').should('be.checked');
		cy.wait(2000); // Let the enabling request finish before navigating away
	});

	it('Publishes the report under Statistics > Reports', function() {
		cy.loginAsManager();
		cy.visit('index.php/publicknowledge/stats/reports');

		cy.contains('a', 'Reviewers Control Report').should('be.visible');
	});
});
