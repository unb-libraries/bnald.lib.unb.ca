@api
Feature: Core
  In order to know the website is running
  As a website user
  I need to be able to view the site title and login

  @api
    Scenario: Login as a user created during this scenario
      Given users:
      | name      | status |
      | Test user |      1 |
      When I am logged in as "Test user"
      Then I should see the link "Log out"
