<?php
namespace Gap\User\Repo;

use Gap\Db\DbManagerInterface;

abstract class RepoBase
{
    protected $dmg;
    protected $cnn;
    protected $cnnName = 'default';

    public function __construct(DbManagerInterface $dmg)
    {
        $this->dmg = $dmg;

        /*
        if (empty($this->cnnName)) {
            throw new \Exception('cnnName cannot be empty');
        }
        */

        $this->cnn = $this->dmg->connect($this->cnnName);
    }
}
