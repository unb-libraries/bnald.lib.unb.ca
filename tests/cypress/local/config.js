const { defineConfig } = require('cypress')

module.exports = defineConfig({
  e2e: {
    reporter: "cypress/reporter/JsonDBReporter.js",
    specPattern: 'cypress/e2e/**/*.cy.js',
    baseUrl: "http://bnald.lib.unb.ca"
  }
})