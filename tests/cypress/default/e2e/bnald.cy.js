describe("British North American Legislation Database (BNALD)", () => {
  context("Front page search form", () => {
    it('Should forward to legislation search page', () => {
      cy.intercept('/legislation/search?query=moose+preservation').as('searchRequest')

      cy.visit("/")
      cy.get('form').within(() => {
        cy.get('input[type="text"]').type('moose preservation')
      }).submit()
      cy.wait('@searchRequest')
    })
  })}
)
