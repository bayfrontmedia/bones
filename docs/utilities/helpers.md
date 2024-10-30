# Utilities: Helpers

The helpers utility has the namespace `Bayfront\Bones\Application\Utilities\Helpers` 
and contains miscellaneous helper functions.
All methods are static.

## Methods

- [classUses](#classuses)
- [traituses](#traituses)

<hr />

### classUses

**Description:**

Recursively return the traits used by the given class and all of its parent classes.

**Parameters:**

- `$class` (object|string)

**Returns:**

- (array)

<hr />

### traitUses

**Description:**

Recursively return all traits used by a trait and its traits.

**Parameters:**

- `$trait` (object|string)

**Returns:**

- (array)