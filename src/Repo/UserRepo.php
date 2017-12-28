<?php
namespace Gap\User\Repo;

use Gap\Open\Dto\UserDto;

class UserRepo extends RepoBase
{
    protected $userTable = 'user';
    protected $passportTable = 'passport';

    public function reg(string $username, string $passhash): void
    {
        $username = trim($username);
        $passhash = trim($passhash);

        $user = new UserDto();
        $user->nick = $username;

        $this->assertNotExists($this->passportTable, 'username', $username);

        $this->cnn->beginTransaction();
        try {
            $this->create($user);
            if (empty($user->userId)) {
                throw new \Exception('userId cannot be empty');
            }
            $this->cnn->insert($this->passportTable)
                ->value('userId', $user->userId)
                ->value('username', $username)
                ->value('passhash', $passhash)
                ->value('created', date('Y-m-d H:i:s'))
                ->execute();
        } catch (\Exception $e) {
            $this->cnn->rollback();
            throw $e;
        }
        $this->cnn->commit();
    }

    public function create(UserDto $user): void
    {

        if (empty($user->userId)) {
            $user->userId = $this->cnn->zid();
        }

        if (empty($user->zcode)) {
            $user->zcode = $this->cnn->zcode();
        }

        $now = date('Y-m-d H:i:s');
        $user->created = $now;
        $user->changed = $now;
        $user->logined = '0000-00-00 00:00:00';

        $this->assertNotExists($this->userTable, 'userId', $user->userId);
        $this->assertNotExists($this->userTable, 'zcode', $user->zcode);

        $this->cnn->insert($this->userTable)
            ->value('nick', $user->nick)
            ->value('userId', $user->userId)
            ->value('zcode', $user->zcode)
            ->value('created', $user->created)
            ->value('changed', $user->changed)
            ->value('logined', $user->logined)
            ->execute();
    }

    public function passhash(string $username): string
    {
        $obj = $this->cnn->select('passhash')
            ->from($this->passportTable)
            ->where('username', '=', $username)
            ->fetchObj();

        if (empty($obj)) {
            throw new \Exception('cannot find ' . $username);
        }

        return $obj->passhash;
    }

    public function fetch(array $query): UserDto
    {
        $ssb = $this->cnn->select(
            ['u', 'userId'],
            ['u', 'nick'],
            ['u', 'zcode'],
            ['u', 'avt'],
            ['u', 'logined'],
            ['u', 'created'],
            ['u', 'changed']
        )->from([$this->userTable, 'u']);

        if ($userId = $query['userId'] ?? '') {
            $ssb->andWhere(['u', 'userId'], '=', $userId);
        }

        if ($zcode = $query['zcode'] ?? '') {
            $ssb->andWhere(['u', 'zcode'], '=', $zcode);
        }

        if ($username = $query['username'] ?? '') {
            $ssb->leftJoin(
                [$this->passportTable, 'p'],
                ['p', 'userId'],
                '=',
                ['u', 'userId']
            );
            $ssb->andWhere('username', '=', $username);
        }

        if (empty($ssb->getWheres())) {
            throw new \Exception('query format error');
        }

        return $ssb->fetch(UserDto::class);
    }

    public function delete(string $userId): void
    {
        $this->cnn->delete()
            ->from($this->userTable)
            ->where('userId', '=', $userId)
            ->execute();
    }

    public function trackLogin(string $userId): void
    {
        $now = date('Y-m-d H:i:s');

        $this->cnn->update($this->userTable)
            ->where('userId', '=', $userId)
            ->set('logined', $now)
            ->execute();
    }

    protected function assertNotExists($table, $col, $val): void
    {
        $obj = $this->cnn->select($col)
            ->from($table)
            ->where($col, '=', $val);

        if ($obj) {
            throw new \Exception("$col already exists in $table");
        }
    }
}
