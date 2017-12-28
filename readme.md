# Readme

The fastest way to create user

## Database

```sql
create database `tec-article`;
grant all privileges on `tec-article`.* to 'tec'@'127.0.0.1' identified by 'pass'
```

```sql
CREATE TABLE `user` (
  `userId` varbinary(64) NOT NULL DEFAULT '',
  `zcode` varbinary(64) NOT NULL DEFAULT '',
  `nick` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `avt` varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `logined` datetime NOT NULL,
  `created` datetime NOT NULL,
  `changed` datetime NOT NULL,
  PRIMARY KEY (`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `passport` (
  `userId` varbinary(64) NOT NULL DEFAULT '',
  `username` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `passhash` varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created` datetime NOT NULL,
  PRIMARY KEY (`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```


## Example
create user
```php
<?php
use Gap\User\UserAdapter;

$userAdapter = new UserAdapter($this->app->get('dmg'));
$username = 'admin';
$password = 'adminpass';
$userAdapter->reg($username, $password);
```

login
```php
<?php
namespace Tec\Article\User\Ui;

use Gap\Http\Response;
use Gap\Http\RedirectResponse;
use Gap\Http\ResponseInterface;
use Gap\User\UserAdapter;

class LoginByUsernameUi extends UiBase
{
    public function show(): ResponseInterface
    {
        $session = $this->app->get('session');
        $loginTargetUrl = $this->request->query->get('target');

        if (empty($session->get('user'))) {
            if ($loginTargetUrl) {
                $session->set('loginTargetUrl', $loginTargetUrl);
            }

            return $this->view('page/user/loginByUsername');
        }

        return new RedirectResponse($this->getLoginTargetUrl($loginTargetUrl));
    }

    public function post(): ResponseInterface
    {
        $username = $this->request->request->get('username');
        $password = $this->request->request->get('password');

        $userAdapter = new UserAdapter($this->app->get('dmg'));

        try {
            $userAdapter->verify($username, $password);
            $user = $userAdapter->fetch(['username' => $username]);

            $session = $this->app->get('session');
            $session->set('user', $user);

            return new RedirectResponse($this->getLoginTargetUrl());
        } catch (\Exception $e) {
            $response = new Response($e->getMessage());
            $response->setStatusCode(500);
            return $response;
        }
        return new Response('login by username');
    }

    protected function getLoginTargetUrl(?string $targetUrl = ''): string
    {
        if ($targetUrl) {
            return $targetUrl;
        }

        $homeUrl = $this->app->get('routeUrlBuilder')->routeGet('home');
        $loginTargetUrl = $this->app->get('session')->get('loginTargetUrl', $homeUrl);
        return $loginTargetUrl;
    }
}
```
