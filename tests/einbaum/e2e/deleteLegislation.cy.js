import { mooseLegislation } from '../fixtures/legislations.json'
import { editor } from "../fixtures/users.json";

describe('Delete a "Piece of Legislation"', () => {
  const { title, path, query } = mooseLegislation

  it('is deleted via form submission', () => {
    cy.loginAs(editor.name)
    cy.visit(path)
    cy.get('[data-test="admin-action-delete"]')
      .click()

    cy.get('[data-test="submit"]')
      .click()

    cy.get('[data-test*="status"]')
      .contains(`The legislation ${title} has been deleted.`)
  })

  it('should not appear in list of most recently added', () => {
    cy.visit('/legislation/recently-added')
    cy.get('[data-test="view-item-0-view_legislation"]')
      .should('not.contain', title)
  });

  it('should not appear in search results', () => {
    cy.visit('/legislation/search')
    cy.get('[data-test="form-element-query"]')
      .type(query)
    cy.get('[data-test="submit"]')
      .click()

    cy.get(`[href="${path}"]`)
      .should('not.exist')
  })
})
