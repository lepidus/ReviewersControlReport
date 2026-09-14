import '../support/commands';

describe('Reviewers Control Report - Plugin enabling', function() {
	it('Enables the plugin', function() {
		cy.loginAsManager();
		cy.goToPluginsGrid();
		cy.route('POST', '**/grid/plugins/plugin-grid/enable*').as('pluginEnabled');

		cy.get('input[id^=select-cell-ReviewersControlReportPlugin]').check();
		cy.wait('@pluginEnabled').its('status').should('eq', 200);
		cy.get('input[id^=select-cell-ReviewersControlReportPlugin]').should('be.checked');
	});

	it('Publishes the report under Statistics > Reports', function() {
		cy.loginAsManager();
		cy.visit('index.php/publicknowledge/stats/reports');

		cy.contains('a', 'Reviewers Control Report').should('be.visible');
	});
});
