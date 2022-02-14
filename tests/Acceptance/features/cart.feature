Feature: Cart
  In order to buy products
  As a visitor
  I need to be able to put products into my cart

  Rules:
  - default country of delivery is Belgium
  - Tax is set to 20%
  - Delivery for a cart under €10 is €3
  - Delivery for a cart over €10 is €2

  Scenario: Buying a single product under €10
    Given there is a "Sith Lord Lightsaber", which costs €5
    And delivery costs €3 for a purchase under €10
    When I add the "Sith Lord Lightsaber" 2 times to the cart
    Then I should have 1 product 2 times in the cart
    And the overall cart price should be €8

  Scenario: Buying a single product over €10
    Given there is a "Sith Lord Lightsaber", which costs €11
    And delivery costs €2 for a purchase over €10
    When I add the "Sith Lord Lightsaber" 2 times to the cart
    Then I should have 1 product 2 times in the cart
    And the overall cart price should be €20
