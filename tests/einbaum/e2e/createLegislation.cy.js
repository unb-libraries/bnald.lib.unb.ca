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
  context('Form access', () => {
    users.forEach(user => {
      const granted = user.name === editor.name
      specify(`${user.name} should ${!granted ? 'not ' : ''}see form link`, () => {
        cy.loginAs(user.name)
        cy.visit('/legislation/recently-added')
        if (granted) {
          cy.get('[data-test="local-action-entity-legislation-add-form"] a')
            .its('0.href')
            .should('match', RegExp(formPath))
        }
        else {
          cy.get('[data-test="local-action-entity-legislation-add-form"]')
            .should('not.exist')
        }
      });

      specify(`${user.name} should be ${granted ? 'granted' : 'denied'} form access`, () => {
        cy.loginAs(user.name)
        cy.request({url: formPath, failOnStatusCode: false})
          .its('status')
          .should('match', granted ? /20\d/ : /40\d/)
      })
    })
  })

  context.only('Form submission', () => {
    let realPath = ''

    before(() => {
      cy.loginAs(editor.name)
      cy.visit(formPath)
    })

    it('should result in a new "Legislation" entity', () => {
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

    it('should appear as the most recently added item', () => {
      cy.visit('/legislation/recently-added')
      cy.get('[data-test="view-item-0-view_legislation"]')
        .should('contain', title)
    });

    it(`should show up in search results for "${query}"`, () => {
      cy.visit('/legislation/search')
      cy.get('[data-test="form-element-query"]')
        .type(query)
      cy.get('[data-test="submit"]')
        .click()

      cy.get('[data-test*="view-item"]')
        .find(`[href="${realPath}"]`)
    })
  })
})
