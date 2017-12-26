## Database
```sql
create database `tec-article`;
grant all privileges on `tec-article`.* to 'tec'@'127.0.0.1' identified by 'pass'
```

```sql
CREATE TABLE `user` (
  `userId` varbinary(21) NOT NULL DEFAULT '',
  `zcode` varbinary(21) NOT NULL DEFAULT '',
  `username` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `passhash` varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `nick` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `avt` varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `logined` datetime NOT NULL,
  `created` datetime NOT NULL,
  `changed` datetime NOT NULL,
  PRIMARY KEY (`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```


## Example
```php
<?php
use Gap\User\UserAdapter;

$userAdapter = new UserAdapter($this->app->get('dmg'));
$username = 'admin';
$password = 'adminpass';
$userAdapter->create($username, $password);
```
