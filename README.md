# Common utility classes and methods with (mostly) static usage

## About
These tools represent functionality common to many website I have created, in which most interface with a relational database.
Many of these sites use multiple files with `includes` that make variable management cumbersome, as top-level PHP variables are globally scoped across all included files.
While moving from globally scoped variables to static class methods doesn't exactly solve anything, I believe it to be slight more elegant.
Two of the most command and/or useful "static" classes in this repository are `PDO` and `QueryString`.

### PDO
The `PDO` class is simply a wrapper for a `\PDO` instance, so you can statically call any `\PDO` method as you would with a normal instance.
The `PDO` class has two additional static methods:
* `connect(...)`, which follows the syntax for `\_PDO::construct()` and must be called before calling any other static method.
* `execute($query, $values)`, which is a combination of `\PDO::prepare()` and `\PDO::bindValue`.  The first argument is a SQL query, and the second argument is an array of typed parameters whose values are to be bound to the query, having the format `['value' => $value, 'type' => \PDO::PARAM_*]`.

### QueryString
While accessing query string variables is simple using `$_GET`, the QueryString class allows for easy manipulation and generation of complete query strings.
It extends the `\ArrayObject` class, and it manipulated using non-static methods, but the `get()` static method returns a new instance.

### Boostrap
Many of the sites I create use the [Bootstrap](https://getbootstrap.com/) frontend toolkit, and this static class provides simple creation for a few common elements, including:
* Alerts (optionally dismissible)
* Modals (optionally static)
* Pagination
* Hyperlinked Sort-control icons for multi-column sorting of data (uses [Bootstrap Icons](https://icons.getbootstrap.com/))
