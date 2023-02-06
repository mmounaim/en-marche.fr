@api
Feature:
  In order to get committees' information
  As a referent
  I should be able to access committees API data


  Scenario: As a non logged-in user I can not access the committee supervisors count managed by referent information
    When I am on "/api/statistics/committees/count-for-referent-area"
    Then the response status code should be 401

  Scenario: As an adherent I can not access the committee supervisors count managed by referent information
    When I am logged as "jacques.picard@en-marche.fr"
    And I am on "/api/statistics/committees/count-for-referent-area"
    Then the response status code should be 401

  Scenario: As a referent I can access the committee supervisors count managed by referent information
    Given I send a "POST" request to "/oauth/v2/token" with parameters:
      | key           | value                                        |
      | client_secret | crOsk2OxtYb4CgnKoYvhb9wvO73QLYyccChiFrV9evE= |
      | client_id     | 4f3394d4-7137-424a-8c73-27e0ad641fc9         |
      | grant_type    | client_credentials                           |
      | scope         | read:stats                                   |
    And I add the access token to the Authorization header
    When I send a "GET" request to "/api/statistics/committees/count-for-referent-area?referent=referent@en-marche-dev.fr"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "committees":4,
      "members": {
        "female":3,
        "male":7,
        "total":10
      },
      "supervisors": {
        "female":2,
        "male":2,
        "total":4
      }
    }
    """

  Scenario: As a non logged-in user I can not get the most active committees in referent managed zone
    When I am on "/api/statistics/committees/top-5-in-referent-area"
    Then the response status code should be 401

  Scenario: As an adherent I can not get the most active committees in referent managed zone
    When I am logged as "jacques.picard@en-marche.fr"
    And I am on "/api/statistics/committees/top-5-in-referent-area"
    Then the response status code should be 401

  Scenario: As a referent I can get the most active committees in referent managed zone
    Given I freeze the clock to "2018-05-18"
    Given I send a "POST" request to "/oauth/v2/token" with parameters:
      | key           | value                                        |
      | client_secret | crOsk2OxtYb4CgnKoYvhb9wvO73QLYyccChiFrV9evE= |
      | client_id     | 4f3394d4-7137-424a-8c73-27e0ad641fc9         |
      | grant_type    | client_credentials                           |
      | scope         | read:stats                                   |
    And I add the access token to the Authorization header
    When I send a "GET" request to "/api/statistics/committees/top-5-in-referent-area?referent=referent@en-marche-dev.fr"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "most_active": [
        {"name":"En Marche Dammarie-les-Lys","events":1},
        {"name":"Antenne En Marche de Fontainebleau","events":1},
        {"name":"En Marche - Suisse","events":1}
      ],
      "least_active": [
        {"name":"En Marche - Suisse","events":1},
        {"name":"Antenne En Marche de Fontainebleau","events":1},
        {"name":"En Marche Dammarie-les-Lys","events":1}
      ]
    }
    """

  Scenario: As a non logged-in user I can not get the committee members count in referent managed zone
    When I am on "/api/statistics/committees/members/count-by-month"
    Then the response status code should be 401

  Scenario: As an adherent I can not get the committee members count in referent managed zone
    When I am logged as "jacques.picard@en-marche.fr"
    And I am on "/api/statistics/committees/members/count-by-month"
    Then the response status code should be 401

  Scenario: As a referent I can get the committee members count in referent managed zone
    Given I freeze the clock to "2018-04-15"
    Given I send a "POST" request to "/oauth/v2/token" with parameters:
      | key           | value                                        |
      | client_secret | crOsk2OxtYb4CgnKoYvhb9wvO73QLYyccChiFrV9evE= |
      | client_id     | 4f3394d4-7137-424a-8c73-27e0ad641fc9         |
      | grant_type    | client_credentials                           |
      | scope         | read:stats                                   |
    And I add the access token to the Authorization header
    When I send a "GET" request to "/api/statistics/committees/members/count-by-month?referent=referent-75-77@en-marche-dev.fr"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "committee_members": [
        {"date": "2018-04", "count":3},
        {"date": "2018-03", "count":3},
        {"date": "2018-02", "count":2},
        {"date": "2018-01", "count":2},
        {"date": "2017-12", "count":2},
        {"date": "2017-11", "count":2}
      ]
    }
    """

    Given I send a "POST" request to "/oauth/v2/token" with parameters:
      | key           | value                                        |
      | client_secret | crOsk2OxtYb4CgnKoYvhb9wvO73QLYyccChiFrV9evE= |
      | client_id     | 4f3394d4-7137-424a-8c73-27e0ad641fc9         |
      | grant_type    | client_credentials                           |
      | scope         | read:stats                                   |
    And I add the access token to the Authorization header
    When I send a "GET" request to "/api/statistics/committees/members/count-by-month?referent=referent-75-77@en-marche-dev.fr&country=fr"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "committee_members": [
        {"date": "2018-04", "count":3},
        {"date": "2018-03", "count":3},
        {"date": "2018-02", "count":2},
        {"date": "2018-01", "count":2},
        {"date": "2017-12", "count":2},
        {"date": "2017-11", "count":2}
      ]
    }
    """

    Given I send a "POST" request to "/oauth/v2/token" with parameters:
      | key           | value                                        |
      | client_secret | crOsk2OxtYb4CgnKoYvhb9wvO73QLYyccChiFrV9evE= |
      | client_id     | 4f3394d4-7137-424a-8c73-27e0ad641fc9         |
      | grant_type    | client_credentials                           |
      | scope         | read:stats                                   |
    And I add the access token to the Authorization header
    When I send a "GET" request to "/api/statistics/committees/members/count-by-month?referent=referent-75-77@en-marche-dev.fr&country=ch"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "committee_members": [
        {"date": "2018-04", "count":0},
        {"date": "2018-03", "count":0},
        {"date": "2018-02", "count":0},
        {"date": "2018-01", "count":0},
        {"date": "2017-12", "count":0},
        {"date": "2017-11", "count":0}
      ]
    }
    """

    Given I send a "POST" request to "/oauth/v2/token" with parameters:
      | key           | value                                        |
      | client_secret | crOsk2OxtYb4CgnKoYvhb9wvO73QLYyccChiFrV9evE= |
      | client_id     | 4f3394d4-7137-424a-8c73-27e0ad641fc9         |
      | grant_type    | client_credentials                           |
      | scope         | read:stats                                   |
    And I add the access token to the Authorization header
    When I send a "GET" request to "/api/statistics/committees/members/count-by-month?referent=referent-75-77@en-marche-dev.fr&city=Paris%208ème"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "committee_members": [
        {"date": "2018-04", "count":2},
        {"date": "2018-03", "count":2},
        {"date": "2018-02", "count":1},
        {"date": "2018-01", "count":1},
        {"date": "2017-12", "count":1},
        {"date": "2017-11", "count":1}
      ]
    }
    """

    Given I send a "POST" request to "/oauth/v2/token" with parameters:
      | key           | value                                        |
      | client_secret | crOsk2OxtYb4CgnKoYvhb9wvO73QLYyccChiFrV9evE= |
      | client_id     | 4f3394d4-7137-424a-8c73-27e0ad641fc9         |
      | grant_type    | client_credentials                           |
      | scope         | read:stats                                   |
    And I add the access token to the Authorization header
    When I send a "GET" request to "/api/statistics/committees/members/count-by-month?referent=referent-75-77@en-marche-dev.fr&city=Dammarie-les-Lys"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "committee_members": [
        {"date": "2018-04", "count":1},
        {"date": "2018-03", "count":1},
        {"date": "2018-02", "count":1},
        {"date": "2018-01", "count":1},
        {"date": "2017-12", "count":1},
        {"date": "2017-11", "count":1}
      ]
    }
    """

    Given I send a "POST" request to "/oauth/v2/token" with parameters:
      | key           | value                                        |
      | client_secret | crOsk2OxtYb4CgnKoYvhb9wvO73QLYyccChiFrV9evE= |
      | client_id     | 4f3394d4-7137-424a-8c73-27e0ad641fc9         |
      | grant_type    | client_credentials                           |
      | scope         | read:stats                                   |
    And I add the access token to the Authorization header
    When I send a "GET" request to "/api/statistics/committees/members/count-by-month?referent=referent-75-77@en-marche-dev.fr&committee=515a56c0-bde8-56ef-b90c-4745b1c93818"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "committee_members": [
        {"date": "2018-04", "count":2},
        {"date": "2018-03", "count":2},
        {"date": "2018-02", "count":1},
        {"date": "2018-01", "count":1},
        {"date": "2017-12", "count":1},
        {"date": "2017-11", "count":1}
      ]
    }
    """

    # Test get stats for committee with scheduled events but not managed by referent
    Given I send a "POST" request to "/oauth/v2/token" with parameters:
      | key           | value                                        |
      | client_secret | crOsk2OxtYb4CgnKoYvhb9wvO73QLYyccChiFrV9evE= |
      | client_id     | 4f3394d4-7137-424a-8c73-27e0ad641fc9         |
      | grant_type    | client_credentials                           |
      | scope         | read:stats                                   |
    And I add the access token to the Authorization header
    When I send a "GET" request to "/api/statistics/committees/members/count-by-month?referent=referent-75-77@en-marche-dev.fr&committee=b0cd0e52-a5a4-410b-bba3-37afdd326a0a"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "committee_members": [
        {"date": "2018-04", "count":1},
        {"date": "2018-03", "count":1},
        {"date": "2018-02", "count":1},
        {"date": "2018-01", "count":1},
        {"date": "2017-12", "count":1},
        {"date": "2017-11", "count":1}
      ]
    }
    """
