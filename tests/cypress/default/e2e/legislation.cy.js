describe("Pieces of Legislation", () => {
  context("Create form", () => {
    specify("bnald_editor should be able to submit", () => {
      cy.drupalLoginAs('bnald_editor')
      cy.visit('/legislation/add')
      cy.fixture("legislationCreate").then(legislation => {
        cy.get('[name="origin[0][target_id]"]').drupalSearchAndSelect(legislation.source)
        cy.get('[name="title[0][value]"]').type(legislation.title)
        cy.get('[name="chapter[0][value]"]').type(legislation.chapter)
        cy.get('[name="year[0][value]"]').type(legislation.year)
        cy.get('[name="article_count[0][value]"]').type(legislation.articleCount)
        cy.get('[name="province[0][target_id]"]').drupalSearchAndSelect(legislation.province)
        cy.get('[name="summary[0][value]"]').type(legislation.summary)
        cy.get('[name="full_text[0][value]"]').type(legislation.fullText)

        cy.get('[name="files[pdf_original_0]"]').selectFile(legislation.pdfOriginal, {waitForUpload: true})
        cy.get('[name="files[pdf_transcribed_0]"]').selectFile(legislation.pdfTranscribed)

        cy.get('[name="jurisdictional_relevance[target_id]"]').drupalSearchAndSelect(legislation.jurisdictionalRelevance)
        cy.get('[name="concepts[target_id]"]').drupalSearchAndSelect(legislation.concepts)

        cy.get('[name="notes[0][value]"]').type(legislation.notes)
      })
    })
  })
})
