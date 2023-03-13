const { defineConfig } = require('@unb-libraries/einbaum')

module.exports = defineConfig({
  cypress: {
    e2e: {
      baseUrl: 'http://localhost:3099',
    },
  },
  plugins: {
    "@unb-libraries/cypress-drupal": {},
  },
})
