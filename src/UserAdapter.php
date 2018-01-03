<?php
namespace Gap\User;

use Gap\Database\DatabaseManager;
use Gap\User\Repo\UserRepo;

use Gap\Open\Dto\UserDto;
use Gap\Open\Dto\AccessTokenDto;
use Gap\Open\Contract\Repo\CreateAccessTokenRepoInterface;

class UserAdapter
{
    protected $dmg;
    protected $userRepo;
    protected $createAccessToken;

    public function __construct(
        DatabaseManager $dmg,
        ?CreateAccessTokenRepoInterface $createAccessToken = null
    ) {
        $this->dmg = $dmg;
        $this->createAccessToken = $createAccessToken;
    }

    public function reg(string $username, string $password): void
    {
        $passhash = password_hash($password, PASSWORD_DEFAULT);

        $this->getUserRepo()->reg($username, $passhash);
    }

    public function create(UserDto $user): void
    {
        $this->getUserRepo()->create($user);
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

    public function accessToken(array $params): AccessTokenDto
    {
        if (!$this->createAccessToken) {
            throw new \Exception('Cannot find "Create access token repo"');
        }

        $appId = $params['appId'] ?? '';
        $userId = $params['userId'] ?? '';
        $ttl = $params['ttl'] ?? new \DateInterval('PT1H');

        $dateFormat = 'Y-m-d H:i:s';

        $created = new \DateTime();
        $expired = new \DateTime();
        $expired->add($ttl);
        $scope = $params['scope'] ?? '';

        $accessToken = new AccessTokenDto([
            'token' => $this->randomCode(),
            'appId' => $appId,
            'userId' => $userId,
            'scope' => $scope,
            'created' => $created->format($dateFormat),
            'expired' => $expired->format($dateFormat)
        ]);
        $this->createAccessToken->create($accessToken);
        return $accessToken;
    }

    protected function getUserRepo(): UserRepo
    {
        if ($this->userRepo) {
            return $this->userRepo;
        }

        $this->userRepo = new UserRepo($this->dmg);
        return $this->userRepo;
    }

    protected function randomCode(): string
    {
        return base64_encode(random_bytes(32));
    }
}
