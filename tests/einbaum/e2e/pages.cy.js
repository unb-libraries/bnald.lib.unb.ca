import pagesFixture from '../fixtures/pages.json'
import usersFixture from '../fixtures/users.json'

const pages = Object.values(pagesFixture)
const users = Object.values(usersFixture)
const { anonymous } = usersFixture

describe('Page', () => {
  pages.forEach(page => {
    context(page.title, () => {

      const access = (page, users, granted = true) => {
        users.forEach(user => {
          if (user.name !== anonymous.name) {
            cy.loginAs(user.name)
          }
          cy.request({url: page.path, failOnStatusCode: false})
            .its('status')
            .should('match', granted ? /20\d/ : /40\d/)
        })
      }

      context('User access', () => {
        const authorized = !page.public && page.role
          ? users.filter(user => user.roles.includes(page.role))
          : !page.public
            ? users.filter(user => user.name !== anonymous.name)
            : users
        const authorizedNames = authorized.map(user => user.name)
        const unauthorized = users.filter(user => !authorizedNames.includes(user.name))

        specify(unauthorized.length > 0 ? `only ${authorizedNames.join(',')}` : 'public access', () => {
          access(page, authorized)
          access(page, unauthorized, false)
        })
      })

      if (page.actions) {
        context('Admin actions', () => {
          users.forEach(user => {
            const actions = user.roles
              .reduce((actions, role) => {
                return {
                  ...actions,
                  ...page.actions[role],
                }
              }, {})
            const actionLabels = Object.values(actions).join(',')

            specify(`${user.name}: ${actionLabels ? actionLabels : 'none'}`, () => {
              if (user.name !== anonymous.name) {
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
        })
      }
    })
  })
})
