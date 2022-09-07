describe("British North American Legislation Database (BNALD)", () => {
  context("Front page search form", () => {
    it('Should forward to legislation search page', () => {
      cy.visit("/")
      cy.get('form').within(() => {
        cy.get('input[type="text"]').type('moose preservation')
      }).submit()
      cy.url()
        .should('match', /\/legislation\/search\?query=moose\+preservation/)
    })
  })
})
