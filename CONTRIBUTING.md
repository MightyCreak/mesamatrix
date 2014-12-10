# How to contribute

So you want to contribute to this small project?

Well thank you! That's very much appreciated! :)

## Coding style

Following the coding style of a project is important. It allows to have a
more readable and maintainable code base. It's also useful for code reviews
since there is no preference here, you simply have to follow the guidelines.
And remember, you control your IDE and not the other way around! ;)

If you have doubts, questions or suggestions, you can contact us on
[IRC](http://webchat.freenode.net/?channels=mesamatrix) or in some other
ways.

### Common style

These rules are the base for all the languages used in the project (PHP,
Javascript, HTML and CSS), unless specified otherwise later in the language
section. Examples are written in PHP.

#### Indentation

- Use 4 spaces instead of tab for indentations

#### Case

- Use `PascalCase` for classes, structures and namespaces
- Use `camelCase` for variables, members, functions and methods
- No prefix and no suffix for the members

Examples:

```php
namespace MyProject\Utils;            // PascalCase for namespaces.

$myVariable = 0;                      // camelCase for variables.

function getSomething($a, $b) {       // camelCase for functions.
    // code here...
}

class MyClass {                               // PascalCase for classes.
    public function getSomething($a, $b) {    // camelCase for methods.
        // code here...
    }

    private $awesomeMember;                   // camelCase for members.
}
```

#### Functions and methods

- Never put spaces after a `(` or before a `)`
- Put a space after `,`
- Put the `{` on the same line
- Put a space before `{`
- Start the body on a new line

**Do**

```php
function doSomething($a, $b) {
    // code here...
}

$c = getSomething($a, $b);
```

**Don't**

```php
function doSomething($a, $b){     // No space before '{'.
    // code here...
}
function doSomething($a, $b)      // '{' is not on the same line.
{
    // code here...
}
function doSomething($a, $b) { /* code */ }    // Definition on one line.

$c = getSomething( $a, $b );      // Bad: spaces after '(' and before ')'.
$c = getSomething($a,$b);         // Bad: no space after ','.
```

#### Control blocks

Same as functions, plus:

- Put a space after the control keyword (`if`, `for`, ...)
- Put `else` in a new line
- Put a space after `;`, but never before
- Put `switch` and `case` on the same column

**Do**

```php
if ($a === $b) {
    // code here...
}
else {
    // code here...
}

for ($i = 0; $i < 10; $i++) {
    // code here...
}

switch ($condition) {
case 1:
    // action1
    break;

case 2:
    // action2;
    break;

default:
    // defaultaction;
    break;
}
```

**Don't**

```php
if($a === $b) {             // No space after 'if'.
    // code here...
} else {                    // 'else' is not on a new line.
    // code here...
}

for ($i=0;$i<10;$i++) {             // No spaces after ';',
    // code here...                 // neither around operators.
}
for ($i = 0 ; $i < 10 ; $i++) {     // Spaces before ';'.
    // code here...
}

switch ($condition) {
    case 1:                 // 'case' is indented.
        break;
}
```

#### Operators

- Always put spaces around the binary or ternary operators (`+`, `*`, `=`,
  `?:`, ...)
- Don't use spaces for unary operators (`-`, `++`, ...)

**Do**

```php
$a = 10;.
$a = $a / 2 + 1;.
$a = $isUsed ? 1 : 2;.
$a = -$b;.
```

**Don't**


```php
$a=10;                 // No spaces around '='.
$a = 10 ;              // Space before ';'.
$a = $a/2+1;           // No spaces around '/' and '+' operators.
$a = $isUsed?1:2;      // No spaces around '?:' operators.
$a = - $b;             // Space after unary '-'.
```

### PHP

- Use `<?php` at the beginning of the page but, at the end, **do not use**
  `?>` since it can write undesirable white spaces to the output
- Use `===` and `!==` instead of `==` and `!=`
- Use `'` for strings

### Javascript

- Always use `var` for your variables
- Use `'` for strings

## HTML

- Use `"` for attributes

## CSS

- Don’t bind your CSS too much to your HTML structure and try to avoid IDs
