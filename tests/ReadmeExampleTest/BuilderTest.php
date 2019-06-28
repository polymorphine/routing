<?php

/*
 * This file is part of Polymorphine/Routing package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Routing\Tests\ReadmeExampleTest;

use Polymorphine\Routing\Tests\ReadmeExampleTest;
use Polymorphine\Routing\Builder;
use Polymorphine\Routing\Router;
use Polymorphine\Routing\Route\Endpoint\CallbackEndpoint;
use Polymorphine\Routing\Tests\Doubles\FakeResponse;
use Polymorphine\Routing\Tests\Doubles\FakeUri;


class BuilderTest extends ReadmeExampleTest
{
    protected function router(): Router
    {
        if ($this->router) { return $this->router; }

        $csrf      = $this->csrfMiddleware();
        $auth      = $this->authMiddleware();
        $adminGate = $this->adminGate();
        $notFound  = $this->notFound();

        $builder = new Builder();
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

        return $this->router = $builder->router(new FakeUri(), new FakeResponse());
    }
}
