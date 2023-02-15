import pages from '../fixtures/pages.json'
import users from '../fixtures/users.json'

describe('Page access', () => {
  pages.forEach(page => {
    context(page.title, () => {

      const access = (page, users, granted = true) => {
        users.forEach(user => {
          cy.loginAs(user.name)
          cy.request({url: page.path, failOnStatusCode: false})
            .its('status')
            .should('match', granted ? /20\d/ : /40\d/)
        })
      }

      const authorized = page.role ? users.filter(user => user.roles.includes(page.role)) : users
      const unauthorized = page.role ? users.filter(user => !user.roles.includes(page.role)) : []
      const grantedUserNames = unauthorized.length > 0
        ? authorized.map(user => user.name).join(',')
        : 'everyone'

      specify(`grant access to ${grantedUserNames}`, () => {
        access(page, authorized)
        access(page, unauthorized, false)
      })
    })
  })
})
