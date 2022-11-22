@check-version
Feature: Get application version
    In order to use application
    As an application user
    I need to be able to get application version

    Scenario: Get application version
        Given the "Content-Type" request header is "application/json"
        When I send request to "/version" using HTTP GET
        And the response code is 200
        And the response body contains JSON:
        """
        {
           "version": "0.1"
        }
        """
