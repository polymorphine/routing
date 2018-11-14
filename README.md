# Polymorphine/Routing
[![Build Status](https://travis-ci.org/shudd3r/polymorphine-routing.svg?branch=develop)](https://travis-ci.org/shudd3r/polymorphine-routing)
[![Coverage Status](https://coveralls.io/repos/github/shudd3r/polymorphine-routing/badge.svg?branch=develop)](https://coveralls.io/github/shudd3r/polymorphine-routing?branch=develop)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/polymorphine/routing/dev-develop.svg)](https://packagist.org/packages/polymorphine/routing)
[![Packagist](https://img.shields.io/packagist/l/polymorphine/routing.svg)](https://packagist.org/packages/polymorphine/routing)
### Composite routing library for HTTP applications

#### Concept feature: *Tree structure finding matching route and resolving urls*
Router may consist of individual routes (see [`Route`](src/Route.php) interface) from
three main categories:
* **Splitters** - Switches (php keyword) that split incoming single route into multiple
route paths.
* **Gates** - Routes that determine if current request should be forwarded or performs
some preprocessing based on request passing through.
* **Endpoints** - Use case entry points. Routes which only responsibility is to take
(processed) request and return response. Neither routing path continuations nor uri
building happens in endpoint routes.

These routes composed in different ways will create unique routing logic, but since
composition tree may be deep its instantiation using `new` operator may become
hard to read by looking at large nested structure or its dependencies assembled
together, but instantiated in order that is reversed to execution flow (nested
structure instantiated first).

[`RoutingBuilder`](src/Builder/RoutingBuilder.php) is a part of this package to help with
the problem. It uses _fluent interface with expressive method names_ - more concise than
class names & their constructors that would be used in direct composition.
It is also more readable due to the fact that builder method calls _resemble execution
path_ in instantiated tree.

![Routing diagram](https://user-images.githubusercontent.com/9908030/48396602-47d3db80-e71b-11e8-9eca-d2cf2e773d37.png)
