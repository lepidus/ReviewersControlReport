import '../support/commands';
import browserContract from '../../tests/browser-contract.json';

describe('Reviewers Control Report - Plugin enabling', function() {
	it('Enables the plugin', function() {
		cy.loginAsManager();
		cy.goToPluginsGrid();
		cy.intercept('POST', '**/grid/settings/plugins/settings-plugin-grid/enable*').as('pluginEnabled');

		cy.get(`input[id^="${browserContract.pluginEnabledInputIdPrefix}"]`).click();
		cy.wait('@pluginEnabled').its('response.statusCode').should('eq', 200);
		cy.get(`input[id^="${browserContract.pluginEnabledInputIdPrefix}"]`).should('be.checked');
	});

	it('Publishes the report under Statistics > Reports', function() {
		cy.loginAsManager();
		cy.visit('/index.php/publicknowledge/en/stats/reports');

		cy.contains('a', 'Reviewers Control Report').should('be.visible');
	});
});
