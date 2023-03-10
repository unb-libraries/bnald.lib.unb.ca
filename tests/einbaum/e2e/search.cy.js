import { winterRoadsLegislation } from '../fixtures/legislations.json'

describe('Search', () => {
  const { query }  = winterRoadsLegislation

  context("Front page search form", () => {
    it('Should forward to legislation search page', () => {
      cy.visit("/")

      cy.get('input[type="text"]')
        .type(query)
      cy.get('[data-test="submit"]')
        .click()

      cy.url()
        .should('match', new RegExp(`\/legislation\/search\\?query=${query.replace(" ", "\\+")}`))
    })
  })
})
