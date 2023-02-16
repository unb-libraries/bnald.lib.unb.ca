import users from "../fixtures/users.json";

describe('Delete a "Piece of Legislation"', () => {
  context(`Form submission`, () => {

    it('should remove the entity', () => {
      const editor = users.find(user => user.roles.includes('bnald_editor'))

      cy.loginAs(editor.name)
      cy.visit('/legislation/act-prevent-destruction-moose-province')
      cy.get('[data-test="admin-action-delete"]')
        .click()

      cy.get('[data-test="submit"]')
        .click()

      cy.get('[data-test*="status"]')
        .contains('The legislation An Act to prevent the destruction of Moose in this Province. has been deleted.')
    })
  })
})
