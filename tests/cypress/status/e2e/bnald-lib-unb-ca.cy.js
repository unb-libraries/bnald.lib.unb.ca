const host = 'https://bnald.lib.unb.ca'
describe('British North American Legislation Database (BNALD)', {baseUrl: host, groups: ['sites']}, () => {

  context('Front page', {baseUrl: host}, () => {
    beforeEach(() => {
      cy.visit('/')
      cy.title()
        .should('contain', 'British North American Legislative Database')
    })

    specify('Search for "moose preservation" should find 2+ results', () => {
      cy.get('#block-simple-search-form form').within(() => {
        cy.get('input[type=text]')
          .type('moose preservation')
      }).submit()
      cy.url()
        .should('match', /\/legislation\/search/)
      cy.get('table tr')
        .should('have.lengthOf.at.least', 2)
    });
  })

})
