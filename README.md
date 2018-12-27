# Polymorphine/Routing
[![Build Status](https://travis-ci.org/shudd3r/polymorphine-routing.svg?branch=develop)](https://travis-ci.org/shudd3r/polymorphine-routing)
[![Coverage Status](https://coveralls.io/repos/github/shudd3r/polymorphine-routing/badge.svg?branch=develop)](https://coveralls.io/github/shudd3r/polymorphine-routing?branch=develop)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/polymorphine/routing/dev-develop.svg)](https://packagist.org/packages/polymorphine/routing)
[![Packagist](https://img.shields.io/packagist/l/polymorphine/routing.svg)](https://packagist.org/packages/polymorphine/routing)
### Composite routing library for HTTP applications

#### Concept feature: *Tree structure finding matching route and resolving urls*
Router may consist of individual routes (see [`Route`](src/Route.php) interface) from
three main categories:
* **Splitters** - Switches that branch single route into multiple route paths. I didn't
  use `switch` name because it's a php keyword and IDE messed up formatting.
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

Diagram below shows control flow of the request passed to matching endpoint in simplified blog page example.

![Routing diagram](https://user-images.githubusercontent.com/9908030/48569332-aeb2e980-e901-11e8-810e-4e447df49ce6.png)

Let's start with it's routing logic description:
1. Request is passed to the router (root)
2. Forwarded request goes through CSRF and (if CSRF guard will allow) Authentication gates (let's assume that
   there are no other registered user roles than admin)
3. In ResponseScan request is forwarded sequentially through each route until response other than "nullResponse"
   is returned.
4. First (default) route will pass request forward only if Authentication marked request as coming from
   page admin.
5. If request was forwarded all meaningful endpoints are available, and if user has no authenticated account
   routes dedicated for unregistered ("guest") user are tested.
6. Of course "guest" user may access almost all pages in read-only mode, so we can forward
   his request to the main tree after guest specific or forbidden options are excluded.
   Next routes will check if user wants to log in, access logout page (which makes no sense so he is redirected)
   or gain unauthorized access to `/admin` path. Beside these, all other read-only (`GET`) endpoints should be
   accessible for guests.
7. If none of previous routes returned meaningful response `GET` requests are allowed to main endpoints tree.
8. While some endpoint access makes sense from guest perspective it is pointless from admin's - for example admin
   trying to log in will be redirected to home page. Guests won't be forwarded here, because this case was
   already resolved for them.

Here's an example showing how to create this structure using routing builder:

    /**
     * assume defined:
     * UriInterface        $baseUri
     * ResponseInterface   $nullResponse
     * MiddlewareInterface $csrf
     * MiddlewareInterface $auth
     * callable            $adminGate
     * callable            $notFound
     * callable            $this->endpoint(string)
     */
    
    $builder = new RoutingBuilder($baseUri, $nullResponse);
    $root    = $builder->rootNode()->middleware($csrf)->middleware($auth)->responseScan();
    
    $main = $root->defaultRoute()->callbackGate($adminGate)->link($filteredGuestRoute)->pathSwitch();
    $main->root('home')->callback($this->endpoint('HomePage'));
    $admin = $main->route('admin')->methodSwitch();
    $admin->route('GET')->callback($this->endpoint('AdminPanel'));
    $admin->route('POST')->callback($this->endpoint('ApplySettings'));
    $main->route('login')->redirect('home');
    $main->route('logout')->method('POST')->callback($this->endpoint('Logout'));
    $articles = $main->resource('articles')->id('id');
    $articles->index()->callback($this->endpoint('ShowArticles'));
    $articles->get()->callback($this->endpoint('ShowArticle'));
    $articles->post()->callback($this->endpoint('AddArticle'));
    $articles->patch()->callback($this->endpoint('UpdateArticle'));
    $articles->delete()->callback($this->endpoint('DeleteArticle'));
    $articles->add()->callback($this->endpoint('AddArticleForm'));
    $articles->edit()->callback($this->endpoint('EditArticleForm'));
    
    $root->route()->path('/login')->methodSwitch([
        'GET'  => new CallbackEndpoint($this->endpoint('LoginPage')),
        'POST' => new CallbackEndpoint($this->endpoint('Login'))
    ]);
    $root->route()->path('/logout')->redirect('home');
    $root->route()->path('/admin')->redirect('login');
    $root->route()->method('GET')->joinLink($filteredGuestRoute);
    $root->route()->callback($notFound);

    $router = $builder->router();

