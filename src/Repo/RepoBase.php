<?php
namespace Gap\User\Repo;

use Gap\Database\DatabaseManager;

abstract class RepoBase
{
    protected $dmg;
    protected $cnn;
    protected $cnnName = 'default';

    public function __construct(DatabaseManager $dmg)
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
