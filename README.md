# Polymorphine Routing
[![Build Status](https://travis-ci.org/shudd3r/polymorphine-routing.svg?branch=develop)](https://travis-ci.org/shudd3r/polymorphine-routing)
[![Coverage Status](https://coveralls.io/repos/github/shudd3r/polymorphine-routing/badge.svg?branch=develop)](https://coveralls.io/github/shudd3r/polymorphine-routing?branch=develop)
### Composite PHP Routing library for HTTP applications

#### Concept feature: *Tree structure finding matching route and resolving urls*
Router may consist of individual routes from three main categories:
* Switches - splitting current route into multiple routes
* Gates - routes that only determine if current request should be forwarded
* Endpoints - routes that take any forwarded request and return response

There are many implementations of `Route` interface with three methods that can:
* `forward()` PSR-7 ServerRequestInterface and return ResponseInterface
* `select()` concrete path in the routing tree
* Return PSR-7 `uri()` for selected path if it can be resolved

These routes composed in different ways will create unique routing logic, and since
composition tree may be deep its instantiation using `new` operator may become
hard to read by looking at large nested structure. `RouteBuilder` is a part of this
package to help with this problem - it uses fluent interface with expressive method
names that will create routes in the same order as they're called.
