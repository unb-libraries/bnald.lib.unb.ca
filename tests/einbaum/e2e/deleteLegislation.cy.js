import { mooseLegislation } from '../fixtures/legislations.json'
import { editor } from "../fixtures/users.json";

describe('Delete a "Piece of Legislation"', () => {
  it('is deleted via form submission', () => {
    const { title, path } = mooseLegislation

    cy.loginAs(editor.name)
    cy.visit(path)
    cy.get('[data-test="admin-action-delete"]')
      .click()

    cy.get('[data-test="submit"]')
      .click()

    cy.get('[data-test*="status"]')
      .contains(`The legislation ${title} has been deleted.`)
  })
})
