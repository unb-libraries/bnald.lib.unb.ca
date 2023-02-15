import pages from '../fixtures/pages.json'
import users from '../fixtures/users.json'

describe('Page access', () => {
  pages.forEach(page => {
    context(page.title, () => {

      const access = (page, users, granted = true) => {
        users.forEach(user => {
          if (user.name !== 'anonymous') {
            cy.loginAs(user.name)
          }
          cy.request({url: page.path, failOnStatusCode: false})
            .its('status')
            .should('match', granted ? /20\d/ : /40\d/)
        })
      }

      const authorized = !page.public && page.role
        ? users.filter(user => user.roles.includes(page.role))
        : !page.public
          ? users.filter(user => user.name !== 'anonymous')
          : users
      const authorizedNames = authorized.map(user => user.name)
      const unauthorized = users.filter(user => !authorizedNames.includes(user.name))
      const grantedUserNames = unauthorized.length > 0
        ? authorizedNames.join(',')
        : 'everyone'

      specify(`grant access to ${grantedUserNames}`, () => {
        access(page, authorized)
        access(page, unauthorized, false)
      })

      if (page.actions) {
        users.forEach(user => {
          const actions = user.roles
            .reduce((actions, role) => {
              return {
                ...actions,
                ...page.actions[role],
              }
            }, {})
          const actionLabels = Object.values(actions).join(',')

          specify(`${user.name} ${actionLabels ? `can ${actionLabels}` : 'has no actions'}`, () => {
            if (user.name !== 'anonymous') {
              cy.loginAs(user.name)
            }
            cy.visit(page.path)
            cy.get('[data-test*="admin-action-"]')
              .should('have.lengthOf', Object.keys(actions).length)
            Object.keys(actions).forEach(action => {
              cy.get(`[data-test="admin-action-${action}"]`)
            })
          })
        })
      }
    })
  })
})
