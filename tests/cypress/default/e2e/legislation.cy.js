describe("Pieces of Legislation", () => {
  context("Create form", () => {
    specify("bnald_editor should be able to submit", () => {
      cy.drupalLoginAs('bnald_editor')
      cy.visit('/legislation/add')
      cy.fixture("legislationCreate").then(legislation => {
        cy.get('[name="origin[0][target_id]"]').type(legislation.source.substr(0, 3))
        cy.get('ul.ui-widget.ui-autocomplete:visible')
          .contains(legislation.source)
          .click()
        cy.get('[name="title[0][value]"]').type(legislation.title)
        cy.get('[name="chapter[0][value]"]').type(legislation.chapter)
        cy.get('[name="year[0][value]"]').type(legislation.year)
        cy.get('[name="article_count[0][value]"]').type(legislation.articleCount)
        cy.get('[name="province[0][target_id]"]').type(legislation.province.substr(0, 3))
        cy.get('ul.ui-widget.ui-autocomplete:visible')
          .contains(legislation.province)
          .click()
        cy.get('[name="summary[0][value]"]').type(legislation.summary)
        cy.get('[name="full_text[0][value]"]').type(legislation.fullText)

        cy.intercept('POST', '/legislation/add?element_parents=pdf_original/widget/0*', req => {
          req.on('response', res => res.setDelay(1000))
        }).as('pdfOriginalUpload')
        cy.get('[name="files[pdf_original_0]"]').selectFile(`${Cypress.config().fixturesFolder}/files/${legislation.pdfOriginal}`)
        cy.wait('@pdfOriginalUpload')
        cy.get('[name="files[pdf_transcribed_0]"]').selectFile(`${Cypress.config().fixturesFolder}/files/${legislation.pdfTranscribed}`)

        legislation.jurisdictionalRelevance.forEach((jurisdictionalRelevance, index) => {
          cy.get('[name="jurisdictional_relevance[target_id]"]').type((index > 0 ? ', ' : '') + jurisdictionalRelevance.substr(0, 3))
          cy.get('ul.ui-widget.ui-autocomplete:visible')
          .contains(jurisdictionalRelevance)
          .click()
        })
        
        legislation.concepts.forEach((concept, index) => {
          cy.get('[name="concepts[target_id]"]').type((index > 0 ? ', ' : '') + concept.substr(0, 3))
          cy.get('ul.ui-widget.ui-autocomplete:visible')
            .contains(concept)
            .click()
        })

        cy.get('[name="notes[0][value]"]').type(legislation.notes)
      })
    })
  })
})
