module.exports = {
  dockworkerMigrate: {
    type: 'before',
    fn: () => {
      cy.exec('vendor/bin/dockworker migrate-rollback legislations', {log: false})
      cy.exec('vendor/bin/dockworker migrate-import --tags=e2e', {log: false})
    }
  },
}
