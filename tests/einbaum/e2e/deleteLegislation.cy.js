import users from "../fixtures/users.json";

const hasPermission = (user) => user.roles.includes('bnald_editor')
const sortByPermission = (u1, u2) => {
  return hasPermission(u1) || !hasPermission(u2)
    ? u1 : u2
}

describe('Delete a "Piece of Legislation"', () => {
  let formPath = ''
  context('Form access', () => {
    users.sort(sortByPermission).forEach(user => {
      const granted = hasPermission(user)

      specify(`${user.name}: ${granted ? 'granted' : 'denied'}`, () => {
        cy.loginAs(user.name)
        cy.visit('/legislation/ordinance-amend-laws-relative-winter-roads')

        if (granted) {
          cy.get('[data-test="local-task-delete"]')
            .contains('Delete')
            .click()

          cy.url().should('match', /legislation\/\d+\/delete/)
          if (!formPath) {
            cy.location('pathname')
              .then(pathname => formPath = pathname)
          }
        }
        else {
          cy.get('[data-test="local-task-delete"]')
            .should('not.exist')
          if (formPath) {
            cy.request({url: formPath, failOnStatusCode: false})
              .its('status')
              .should('match', /40\d/)
          }
        }
      })
    })
  })

  context(`Form submission`, () => {
    const editor = users.find(hasPermission)

    it('should remove the entity', () => {
      cy.loginAs(editor.name)
      cy.visit(formPath)

      cy.get('[data-test="submit"]')
        .click()
      cy.get('[data-test*="status"]')
        .contains('The legislation An Ordinance to amend the Laws relative to Winter Roads. has been deleted.')
    })
  })
})
