<?php

declare(strict_types=1);

namespace AdminBundle\Admin;

use CoreBundle\Entity\User as UserEntity;
use CoreBundle\Service\User as UserService;
use Doctrine\Common\Collections\Criteria;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class UserAdmin extends BaseAdmin
{
    public static array $config = [
        'roles' => ['ROLE_SONATA_ADMIN'],
    ];

    private readonly UserService $userService;

    public function __construct(
        string $code,
        string $class,
        string $baseControllerName,
        TokenStorageInterface $tokenStorage,
        UserService $userService,
    ) {
        parent::__construct($code, $class, $baseControllerName, $tokenStorage);
        self::setConfig(self::$config);
        $this->userService = $userService;
    }

    public function validatePhone(?string $value, ExecutionContextInterface $context): void
    {
        if (!$value) {
            return;
        }

        try {
            $this->userService->getFormattedPhone($value);
        } catch (\Exception $exception) {
            $context->buildViolation($exception->getMessage())
                ->addViolation();
        }
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection
            ->remove('delete')
            ->add('copy_roles', 'copy-roles')
            ->add('toggle_enabled', $this->getRouterIdParameter().'/toggle-enabled');
    }

    #[\Override]
    protected function configureActionButtons(array $buttonList, string $action, ?object $object = null): array
    {
        unset($action, $object);
        $buttonList['copyRoles'] = ['template' => 'admin/user/copy_roles_button.html.twig'];

        return $buttonList;
    }

    #[\Override]
    protected function configureQuery(ProxyQueryInterface $query): ProxyQueryInterface
    {
        $query->getQueryBuilder()
            ->orderBy(\sprintf('%s.id', $query->getRootAlias()), Criteria::DESC);

        return parent::configureQuery($query);
    }

    protected function prePersist(object $object): void
    {
        // add default role
        $defaultRole = ['ROLE_USER'];
        $object->setRoles(array_merge($object->getRoles(), [$defaultRole]));
        $this->userService->setPassword($object);
    }

    protected function preUpdate(object $object): void
    {
        // add default role
        $defaultRole = ['ROLE_USER'];
        $object->setRoles(array_merge($object->getRoles(), [$defaultRole]));
        $this->userService->setPassword($object);
        $this->validateUsername($object);

        if (!$object->getEmail()) {
            $object->setEmail($this->generateEmail($object->getUsername()));
        }

        if (str_contains($object->getEmail(), 'buddyscript.com')) {
            $object->setEmail($this->generateEmail($object->getUsername()));
        }
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('id')
            ->add('name')
            ->add('username')
            ->add('email')
            ->add('phone')
            ->add('enabled');
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('id')
            ->add('name')
            ->add('username')
            ->add('email')
            ->add('phone')
            ->add('enabled')
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => [
                    'show' => [],
                    'edit' => [],
                    'toggle_enabled' => [
                        'template' => 'admin/user/list__action_toggle_enabled.html.twig',
                    ],
                ],
            ]);
    }

    protected function configureFormFields(FormMapper $form): void
    {
        /** @var UserEntity $user */
        $user = $this->getSubject();
        $isNew = $this->isCurrentRoute('create');
        $ppConstraint = [new Length(['min' => 6])];
        if ($isNew) {
            $ppConstraint[] = new NotBlank();
        }

        $form
            ->tab('General')
            ->add('name', null, [
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 4, 'max' => 50]),
                ],
            ])
            ->add('username', null, [
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 4, 'max' => 50]),
                ],
                'attr' => [
                    'readonly' => !$isNew,
                ],
            ])
            ->add('email', null, [
                'constraints' => [
                    new Email([
                        'message' => 'The email {{ value }} is not a valid email.',
                    ]),
                ],
            ])
            ->add('plainPassword', null, [
                'constraints' => $ppConstraint,
            ])
            ->add('phone', null, [
                'constraints' => [new Callback([$this, 'validatePhone']), new NotBlank()],
                'required' => true,
            ])
            ->add('roles', ChoiceType::class, [
                'choices' => $this->userService->getAllFlattenedRoles(),
                'multiple' => true,
                'required' => false,
                'data' => !$isNew ? $user->getRoles() : null,
            ])
            ->end()
            ->end()
        ;
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->tab('General')
            ->add('id')
            ->add('name')
            ->add('username')
            ->add('email')
            ->add('phone')
            ->add('enabled')
            ->add('roles', null, ['template' => 'admin/user/show_roles.html.twig'])
            ->end()
            ->end();
    }

    protected function generateEmail(string $username): string
    {
        return \sprintf('%s@buddyscript.com', $username);
    }

    private function validateUsername(object $object): void
    {
        $em = $this->getModelManager()->getEntityManager($this->getClass());
        $original = $em->getUnitOfWork()->getOriginalEntityData($object);

        if ($object->getUsername() !== $original['username']) {
            throw new \RuntimeException('Username is not editable.');
        }
    }
}
