Feature: Restaurant ERP Costing, Supply Chain, and SOP Management
  As a Restaurant Manager
  I want to manage ingredients, recipes (SOP), and inventory
  So that I can control food costs and maintain standard quality

  Background:
    Given I am logged in as a "Restaurant Manager"

  # --- Supply Chain & Inventory ---

  Scenario: Create a new Ingredient with Supplier information
    Given I am on the "Ingredient Management" page
    When I create a new ingredient "Fresh Salmon"
    And I assign the supplier "Ocean Fresh Seafoods"
    And I set the purchase unit as "kg" and cost per unit as "500.00" THB
    Then the ingredient "Fresh Salmon" should be saved successfully
    And the current stock level should be "0"

  Scenario: Receive Stock (Supply Chain)
    Given the ingredient "Fresh Salmon" exists with current stock "0" kg
    When I record a "Goods Receipt" for "Fresh Salmon"
    And I enter the quantity "10" kg
    And I enter the received date as "today"
    Then the stock level for "Fresh Salmon" should be "10" kg
    And the inventory value should increase by "5000.00" THB

  # --- SOP & Costing ---

  Scenario: Create a Recipe SOP (Standard Operating Procedure)
    Given the following ingredients exist:
      | Name           | Cost (THB) | Unit |
      | Fresh Salmon   | 500.00     | kg   |
      | Olive Oil      | 200.00     | Liter|
      | Lemon          | 10.00      | Piece|
    When I create a new Recipe SOP named "Grilled Salmon Steak"
    And I add the instruction step "Clean and pat dry the salmon"
    And I add the instruction step "Season with salt and pepper"
    And I add the instruction step "Grill for 5 minutes each side"
    And I add the following ingredients to the recipe:
      | Ingredient     | Quantity | Unit  |
      | Fresh Salmon   | 0.2      | kg    |
      | Olive Oil      | 0.01     | Liter |
      | Lemon          | 0.5      | Piece |
    Then the recipe "Grilled Salmon Steak" should be saved
    And the calculated food cost should be "107.00" THB
    # Calculation: (500*0.2) + (200*0.01) + (10*0.5) = 100 + 2 + 5 = 107

  Scenario: View Recipe SOP details
    Given the recipe "Grilled Salmon Steak" exists
    When I view the SOP for "Grilled Salmon Steak"
    Then I should see the preparation steps
    And I should see the list of ingredients
    And I should see the total cost per serving
