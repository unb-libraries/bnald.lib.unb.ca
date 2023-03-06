module.exports = {
  dockworkerMigrate: {
    type: 'before',
    fn: () => {
      cy.exec('dockworker migrate-rollback legislations', {log: false})
      cy.exec('dockworker migrate-import --tags=e2e', {log: false})
    }
  },
}
