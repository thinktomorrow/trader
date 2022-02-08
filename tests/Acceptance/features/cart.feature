Feature: Cart
  In order to buy products
  As a visitor
  I need to be able to put products into my cart

  Rules:
  - default country of delivery is Belgium
  - default VAT is 21%
  - Delivery for a cart under €10 is €3
  - Delivery for a cart over €10 is €2

  Scenario: Buying a single product under €10
    Given there is a "Sith Lord Lightsaber", which costs €5
    When I add the "Sith Lord Lightsaber" to the cart
    Then I should have 1 product in the cart
    And the overall cart price should be €8

  Scenario: Buying a single product over €10
    Given there is a "Sith Lord Lightsaber", which costs €11
    When I add the "Sith Lord Lightsaber" to the cart
    Then I should have 1 product in the cart
    And the overall cart price should be €13
