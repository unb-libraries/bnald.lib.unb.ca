describe('Creating a "Piece of Legislation"', () => {
  context('as "BNALD editor"', () => {
    before(() => {
      cy.loginAs('bnald_editor')
      cy.visit('/legislation/add')
    })
    
    it('should result in a new "Legislation" entity', () => {
      cy.get('[data-test="origin"]')
        .enter("Acts of the General Assembly of His Majesty's Province of New Brunswick")
      cy.get('[data-test="title"]')
        .enter('An Act to incorporate the Richibucto Boom Company. Passed 17th June 1867.')
      cy.get('[data-test="chapter"]')
        .enter('30 Victoria - Chapter 85')
      cy.get('[data-test="year"]')
        .enter(1867)
      cy.get('[data-test="article_count"]')
        .enter(18)
      cy.get('[data-test="province"]')
        .enter('New Brunswick')
      cy.get('[data-test="summary"]')
        .enter('This Act incorporates the Richibucto Boom Company, their powers, and the responsibilities surrounding their equipment.')
      cy.get('[data-test="full_text"]')
        .enter("Whereas the erection of a Boom or Booms at or near the bridge over the Richibucto River near Anthony Cail's, in the County of Kent, will be a great benefit...")
      cy.get('[data-test="pdf_original"]')
        .enter('original.pdf')
      cy.get('[data-test="pdf_transcribed"]')
        .enter('transcribed.pdf')
      cy.get('[data-test="jurisdictional_relevance"]')
        .enter('Local')
      cy.get('[data-test="concepts"]')
        .enter('Natural Resources')
      
      cy.get('[data-test="submit"]')
        .click()

      cy.location('pathname')
        .should('match', /legislation\/act-incorporate-richibucto-boom-company-passed-17th-june-1867(-\d+)?/)
      cy.get('[data-test*="status"]')
        .contains('Created the An Act to incorporate the Richibucto Boom Company. Passed 17th June 1867. Legislation.')
    })
  })

  context('as another logged-in user', () => {
    it('should result in an "Access denied" page', () => {
      cy.loginAs('user')
      cy.visit('/legislation/add', {failOnStatusCode: false})
      cy.get('[data-test="page-title"]')
        .contains('Access denied')
    })
  })

  context('as an anonymous user', () => {
    it('should result in an "Access denied" page', () => {
      cy.visit('/legislation/add', {failOnStatusCode: false})
      cy.get('[data-test="page-title"]')
        .contains('Access denied')
    })
  })
})
