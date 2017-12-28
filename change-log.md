# Change log

## 1.0.4

### Changed

- Fix fatal error in assert not exists


## 1.0.3

### Changed

- remove cols username, passhash from table user
- create table `passport`
- remove function UserAdapter::create(string $username, string $password): void
- create function UserAdapter::reg(string $username, string $password): void
- create function UserAdapter::create(UserDto $user): void
