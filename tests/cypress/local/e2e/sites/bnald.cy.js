describe("British North American Legislation Database (BNALD)", () => {
  context("Front page", () => {
    it('Should not be found', () => {
      cy.visit("/", {failOnStatusCode: false})
      cy.get('h2.page-header')
        .contains('Page not found')
    })
  })}
)
