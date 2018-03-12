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
                ->value(
                    $this->cnn->value()
                        ->add($this->cnn->str($user->userId))
                        ->add($this->cnn->str($username))
                        ->add($this->cnn->str($passhash))
                        ->add($this->cnn->dateTime(new \DateTime()))
                )
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
            ->value(
                $this->cnn->value()
                    ->add($this->cnn->str($user->nick))
                    ->add($this->cnn->str($user->userId))
                    ->add($this->cnn->str($user->zcode))
                    ->add($this->cnn->dateTime($user->created))
                    ->add($this->cnn->dateTime($user->changed))
                    ->add($this->cnn->dateTime($user->logined))
            )
            ->execute();
    }

    public function passhash(string $username): string
    {
        $obj = $this->cnn->select('passhash')
            ->from($this->cnn->table($this->passportTable))
            ->where(
                $this->cnn->cond()
                    ->expect('username')->equal($this->cnn->str($username))
            )
            ->fetchAssoc();

        if (empty($obj)) {
            throw new \Exception('cannot find ' . $username);
        }

        return $obj['passhash'];
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
        );

        $table = $this->cnn->table($this->userTable . ' u');
        $cond = $this->cnn->cond();
        
        if ($userId = $query['userId'] ?? '') {
            $cond->expect('u.userId')->equal($this->cnn->str($userId));
            return $select->from($table)->where($cond)
                ->fetch(UserDto::class);
        } elseif ($zcode = $query['zcode'] ?? '') {
            $cond->expect('u.zcode')->equal($this->cnn->str($zcode));

            return $select->from($table)->where($cond)
                ->fetch(UserDto::class);
        } elseif ($username = $query['username'] ?? '') {
            $table->leftJoin($this->passportTable . ' p')
                ->onCond(
                    $this->cnn->cond()
                        ->expect('p.userId')->equal($this->cnn->str('u.userId'))
                );
            $cond->expect('p.username')->equal($this->cnn->str($username));

            return $select->from($table)->where($cond)
                ->fetch(UserDto::class);
        }

        throw new \Exception('query format error');
    }

    public function delete(string $userId): void
    {
        $this->cnn->delete()
            ->from($this->cnn->table($this->userTable))
            ->where(
                $this->cnn->cond()
                    ->expect('userId')->equal($this->cnn->str($userId))
            )
            ->execute();
    }

    public function trackLogin(string $userId): void
    {
        $this->cnn->update($this->cnn->table($this->userTable))
            ->set('logined', $this->cnn->dateTime(new \DateTime()))
            ->where(
                $this->cnn->cond()
                    ->expect('userId')->equal($this->cnn->str($userId))
            )
            ->execute();
    }

    protected function assertNotExists($table, $col, $val): void
    {
        $arr = $this->cnn->select($col)
            ->from($this->cnn->table($table))
            ->where(
                $this->cnn->cond()
                    ->expect($col)->equal($this->cnn->str($val))
            )
            ->fetchAssoc();

        if ($arr) {
            throw new \Exception("$col '$val' already exists in $table");
        }
    }
}
