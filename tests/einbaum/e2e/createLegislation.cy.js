import users from '../fixtures/users.json'

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
    before(() => {
      cy.loginAs(editor.name)
      cy.visit(formPath)
    })

    it('should result in a new "Legislation" entity', () => {
      Object.entries({
        origin: "Acts of the General Assembly of His Majesty's Province of New Brunswick",
        title: 'An Act to incorporate the Richibucto Boom Company. Passed 17th June 1867.',
        chapter: '30 Victoria - Chapter 85',
        year: 1867,
        article_count: 18,
        province: 'New Brunswick',
        summary: 'This Act incorporates the Richibucto Boom Company, their powers, and the responsibilities surrounding their equipment.',
        full_text: "Whereas the erection of a Boom or Booms at or near the bridge over the Richibucto River near Anthony Cail's, in the County of Kent, will be a great benefit...",
        pdf_original: 'original.pdf',
        pdf_transcribed: 'transcribed.pdf',
        jurisdictional_relevance: 'Local',
        concepts: 'Natural Resources',
      }).forEach(([field, value]) => cy.get(`[data-test="${field}"`).enter(value))

      cy.get('[data-test="submit"]')
        .click()

      cy.location('pathname')
        .should('match', /legislation\/act-incorporate-richibucto-boom-company-passed-17th-june-1867(-\d+)?/)
      cy.get('[data-test*="status"]')
        .contains('Created the An Act to incorporate the Richibucto Boom Company. Passed 17th June 1867. Legislation.')
    })

    it('should appear as the most recently added item', () => {
      cy.visit('/legislation/recently-added')
      cy.get('[data-test="view-item-0-view_legislation"]')
        .should('contain', 'An Act to incorporate the Richibucto Boom Company. Passed 17th June 1867.')
    });
  })
})
