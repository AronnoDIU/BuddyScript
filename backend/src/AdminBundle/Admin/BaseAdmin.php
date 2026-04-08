<?php

namespace AdminBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;

class BaseAdmin extends AbstractAdmin
{
    public static array $config = [
        'showInDashboard' => false,
        'roles' => [],
    ];

    protected array $datagridValues = [
        '_page' => 1,
        '_sort_order' => 'desc',
        '_sort_by_col' => 2,
    ];

    protected array $perPageOptions = ['All'];

    protected TokenStorageInterface $tokenStorage;

    public function __construct(
        string $code,
        string $class,
        string $baseControllerName,
        TokenStorageInterface $tokenStorage,
    ) {
        parent::__construct($code, $class, $baseControllerName);

        $this->tokenStorage = $tokenStorage;
    }

    public function getLoggedUser(): UserInterface
    {
        return $this
            ->tokenStorage
            ->getToken()
            ->getUser();
    }

    protected static function setConfig(array $config): void
    {
        self::$config = $config;
    }

    private function isUserAllowed(array $roles = []): bool
    {
        $user = $this->getLoggedUser();

        if ($user->hasRole('ROLE_SUPER_ADMIN')) {
            return true;
        }

        foreach ($roles as $role) {
            try {
                if ($this->isGranted($role)) {
                    return true;
                }
            } catch (\JsonException $e) {
                throw new \RuntimeException($e->getMessage());
            }
        }

        throw new AccessDeniedException('Access Denied');
    }

    private function getConfigSession(): array
    {
        $session = $this->getRequest()->getSession();
        $key = $this->getConfigSessionKey();

        return $session->get($key);
    }

    private function setConfigSession(): void
    {
        $session = $this->getRequest()->getSession();
        $key = $this->getConfigSessionKey();
        $session->set($key, self::$config);
    }

    private function getConfigSessionKey(): string
    {
        if (str_contains($this->getCode(), 'sonata.dashboard')) {
            $route = str_replace('.', '_', substr($this->getCode(), 7));
            $redirectUrl = $this->getContainer()->get('router')->generate($route);
            $redirection = new RedirectResponse($redirectUrl);
            $redirection->send();
        }

        try {
            $class = new \ReflectionClass($this->getClass());
        } catch (\ReflectionException $e) {
            throw new \RuntimeException($e->getMessage());
        }

        return $class->name
                |> strtolower(...)
                |> (static fn($x) => str_replace('\\', '-', $x))
                |> (static fn($x) => \sprintf('admin-%s', $x));
    }
}
