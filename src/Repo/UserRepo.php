<?php
namespace Gap\User\Repo;

use Gap\Open\Dto\UserDto;

class UserRepo extends RepoBase
{
    protected $userTable = 'open_user';
    protected $passportTable = 'user_passport';

    public function reg(string $username, string $passhash): void
    {
        $username = trim($username);
        $passhash = trim($passhash);

        $user = new UserDto();
        $user->nick = $username;

        $this->assertNotExists($this->passportTable, 'username', $username);

        $this->cnn->trans()->begin();
        try {
            $this->create($user);
            if (empty($user->userId)) {
                throw new \Exception('userId cannot be empty');
            }
            $this->cnn->insert($this->passportTable)
                ->field('userId', 'username', 'passhash', 'created')
                ->value()
                    ->addStr($user->userId)
                    ->addStr($username)
                    ->addStr($passhash)
                    ->addDateTime(new \DateTime())
                ->execute();
        } catch (\Exception $e) {
            $this->cnn->trans()->rollback();
            throw $e;
        }
        $this->cnn->trans()->commit();
    }

    public function create(UserDto $user): void
    {

        if (empty($user->userId)) {
            $user->userId = $this->cnn->zid();
        }

        if (empty($user->zcode)) {
            $user->zcode = $this->cnn->zcode();
        }

        //$now = date('Y-m-d H:i:s');
        $now = new \DateTime();
        $user->created = $now;
        $user->changed = $now;
        $user->logined = new \DateTime('0000-1-1');

        $this->assertNotExists($this->userTable, 'userId', $user->userId);
        $this->assertNotExists($this->userTable, 'zcode', $user->zcode);

        $this->cnn->insert($this->userTable)
            ->field('nick', 'userId', 'zcode', 'created', 'changed', 'logined')
            ->value()
                ->addStr($user->nick)
                ->addStr($user->userId)
                ->addStr($user->zcode)
                ->addDateTime($user->created)
                ->addDateTime($user->changed)
                ->addDateTime($user->logined)
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
        $select = $this->cnn->select(
            'u.userId',
            'u.nick',
            'u.zcode',
            'u.avt',
            'u.logined',
            'u.created',
            'u.changed'
        )->from($this->userTable . ' u')->where();

        if ($userId = $query['userId'] ?? '') {
            return $select
                ->expect('u.userId')->beStr($userId)
                ->execute()
                ->fetch(UserDto::class);
        }

        if ($zcode = $query['zcode'] ?? '') {
            return $select
                ->expect('u.zcode')->beStr($zcode)
                ->execute()
                ->fetch(UserDto::class);
        }

        if ($username = $query['username'] ?? '') {
            $select->leftJoin($this->passportTable . ' p')
                ->onCond()
                    ->expect('p.userId')->beExpr('u.userId')
                ->where()
                    ->expect('p.username')->beStr($username)
                ->execute()
                ->fetch(UserDto::class);
        }

        throw new \Exception('query format error');
    }

    public function delete(string $userId): void
    {
        $this->cnn->delete()
            ->from($this->userTable)
            ->where()
                ->expect('userId')->beStr($userId)
            ->execute();
    }

    public function trackLogin(string $userId): void
    {
        $this->cnn->update($this->userTable)
            ->set('logined')->beDateTime(new \DateTime())
            ->where()
                ->expect('userId')->beStr($userId)
            ->execute();
    }

    protected function assertNotExists($table, $col, $val): void
    {
        $arr = $this->cnn->select($col)
            ->from($table)
            ->where()
                ->expect($col)->beStr($val)
            ->execute()
            ->fetchAssoc();

        if ($arr) {
            throw new \Exception("$col '$val' already exists in $table");
        }
    }
}
