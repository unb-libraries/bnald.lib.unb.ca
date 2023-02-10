describe('Deleting a "Piece of Legislation"', () => {
  context('as another logged-in user', () => {
    it('should not be permitted', () => {
      cy.loginAs('user')
      cy.visit('/legislation/ordinance-amend-laws-relative-winter-roads')
      cy.get('[data-test="local-task-delete"]')
        .should('not.exist')
      cy.visit('/legislation/2/delete', {failOnStatusCode: false})
      cy.get('[data-test="page-title"]')
        .should('contain', 'Page not found')
    });
  })

  context('as anonymous user', () => {
    it('should not be permitted', () => {
      cy.visit('/legislation/ordinance-amend-laws-relative-winter-roads')
      cy.get('[data-test="local-task-delete"]')
        .should('not.exist')
      cy.visit('/legislation/2/delete', {failOnStatusCode: false})
      cy.get('[data-test="page-title"]')
        .should('contain', 'Page not found')
    });
  })

  context('as "BNALD editor"', () => {
    it('should remove a "Legislation" entity', () => {
      cy.loginAs('bnald_editor')
      cy.visit('/legislation/ordinance-amend-laws-relative-winter-roads')
      cy.get('[data-test="local-task-delete"]')
        .click()
      cy.url()
        .should('match', /legislation\/\d+\/delete/)
      cy.get('[data-test="submit"]')
        .click()
      cy.get('[data-test*="status"]')
        .contains('The legislation An Ordinance to amend the Laws relative to Winter Roads. has been deleted.')
    })
  })
})
