import users from '../fixtures/users.json'
import legislations from '../fixtures/legislations.json'

const {
  origin,
  title,
  path,
  chapter,
  year,
  article_count,
  province,
  summary,
  full_text,
  pdf_original,
  pdf_transcribed,
  jurisdictional_relevance,
  concepts,
  query,
} = legislations.find(legislation => legislation.id < 0)

const editor = users.find(user => user.roles.includes('bnald_editor'))
const formPath = '/legislation/add'

describe('Creating a "Piece of Legislation"', () => {
  let realPath = ''

  before(() => {
    cy.loginAs(editor.name)
    cy.visit(formPath)
  })

  specify(`is created via form submission`, () => {
    Object.entries({
      origin,
      title,
      chapter,
      year,
      article_count,
      province,
      summary,
      full_text,
      pdf_original,
      pdf_transcribed,
      jurisdictional_relevance,
      concepts,
    }).forEach(([field, value]) => {
      cy.get(`[data-test="${field}"`)
        .enter(value)
    })

    cy.get('[data-test="submit"]')
      .click()

    cy.location('pathname').then(pathname => realPath = pathname)
      .should('contain', path)
    cy.get('[data-test*="status"]')
      .contains(`Created the ${title} Legislation`)
  })

  specify('tops list of most recently added', () => {
    cy.visit('/legislation/recently-added')
    cy.get('[data-test="view-item-0-view_legislation"]')
      .should('contain', title)
  });

  specify(`shows up in search results`, () => {
    cy.visit('/legislation/search')
    cy.get('[data-test="form-element-query"]')
      .type(query)
    cy.get('[data-test="submit"]')
      .click()

    cy.get('[data-test*="view-item"]')
      .find(`[href="${realPath}"]`)
  })
})
