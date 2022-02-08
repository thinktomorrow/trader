# Trader development

## Code structure
The current code structure consists of four main directories:
- Trader: package code to be included in each project and contains the interfaces and default classes
- Shop: Project specific Shop logic, Frontend controllers and handling
- ShopAdmin: Chief specific classes for management of the shop via Chief.

## Adding a product field
- If not a dynamic field, you'll need to change the create() and save() method of your ProductRepository class
- If not a dynamic field, In the ProductRepository add this field to the productArguments method.
- add to chief product model as field definition. When you want to use a dynamic field, you'll need to add the dynamic key as well.
