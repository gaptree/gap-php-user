<?php
namespace Gap\User\Repo;

use Gap\Open\Dto\UserDto;

class UserRepo extends RepoBase
{
    protected $table = 'user';

    public function create(string $username, string $passhash)
    {
        $now = date('Y-m-d H:i:s');
        $logined = '0000-00-00 00:00:00';

        $this->cnn->insert($this->table)
            ->value('username', $username)
            ->value('passhash', $passhash)
            ->value('nick', $username)
            ->value('userId', $this->cnn->zid())
            ->value('zcode', $this->cnn->zcode())
            ->value('created', $now)
            ->value('changed', $now)
            ->value('logined', $logined)
            ->execute();
    }

    public function passhash(string $username): string
    {
        $obj = $this->cnn->select('isActive', 'passhash')
            ->from($this->table)
            ->where('username', '=', $username)
            ->fetchObj();

        if (intval($obj->isActive) === 0) {
            throw new \Exception('user not ative');
        }

        return $obj->passhash;
    }

    public function fetch(array $query): UserDto
    {
        $ssb = $this->cnn->select(
            'userId',
            'nick',
            'zcode',
            'avt',
            'logined',
            'created',
            'changed'
        )->from($this->table);

        if ($userId = $query['userId'] ?? '') {
            $ssb->andWhere('userId', '=', $userId);
        }

        if ($username = $query['username'] ?? '') {
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
            ->from($this->table)
            ->where('userId', '=', $userId)
            ->execute();
    }

    public function trackLogin(string $userId): void
    {
        $now = date('Y-m-d H:i:s');

        $this->cnn->update($this->table)
            ->where('userId', '=', $userId)
            ->set('logined', $now)
            ->execute();
    }
}
