const { defineConfig } = require('@unb-libraries/einbaum')

module.exports = defineConfig({
  baseUrl: 'http://local-bnald.lib.unb.ca:3099',
  plugins: {
    "@unb-libraries/cypress-drupal": {},
  },
})
