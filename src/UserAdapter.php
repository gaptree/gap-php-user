<?php
namespace Gap\User;

use Gap\Database\DatabaseManager;
use Gap\Open\Dto\UserDto;
use Gap\User\Repo\UserRepo;

class UserAdapter
{
    protected $dmg;
    protected $userRepo;

    public function __construct(DatabaseManager $dmg)
    {
        $this->dmg = $dmg;
    }

    public function create(string $username, string $password): void
    {
        $passhash = password_hash($password, PASSWORD_DEFAULT);

        $this->getUserRepo()->create($username, $passhash);
    }

    public function verify(string $username, string $password): void
    {
        $passhash = $this->getUserRepo()->passhash($username);
        if (!password_verify($password, $passhash)) {
            throw new \Exception('password not match');
        }
    }

    public function fetch(array $query): UserDto
    {
        return $this->getUserRepo()->fetch($query);
    }

    public function delete(string $userId): void
    {
        $this->getUserRepo()->delete($userId);
    }

    public function trackLogin(string $userId): void
    {
        $this->getUserRepo()->trackLogin($userId);
    }

    protected function getUserRepo(): UserRepo
    {
        if ($this->userRepo) {
            return $this->userRepo;
        }

        $this->userRepo = new UserRepo($this->dmg);
        return $this->userRepo;
    }
}
