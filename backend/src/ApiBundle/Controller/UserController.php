<?php

namespace ApiBundle\Controller;

use ApiBundle\Exception\ValidationException;
use ApiBundle\Service\Api as ApiService;
use ApiBundle\Validation\UserValidator;
use CoreBundle\Entity\User as UserEntity;
use CoreBundle\Service\User as UserService;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class UserController
 */
#[Route(path: '/user', name: 'api.user.')]
class UserController extends BaseController
{
    private readonly UserService $userService;

    private readonly UserValidator $userValidator;

    public function __construct(ApiService $apiService, UserService $userService, UserValidator $userValidator)
    {
        parent::__construct($apiService);
        $this->userService = $userService;
        $this->userValidator = $userValidator;
    }

    #[Rest\View(serializerGroups: ['user_details'])]
    #[Route(name: 'list', methods: ['GET'])]
    #[OA\Tag(name: 'User')]
    #[OA\Parameter(name: 'id', in: 'query', schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'app_ids', in: 'query', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'user_name', in: 'query', schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'email', in: 'query', schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'phone', in: 'query', schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'role', in: 'query', schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'enabled', in: 'query', schema: new OA\Schema(type: 'bool'))]
    #[OA\Response(
        response: 200,
        description: 'Returns the list of user.',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                ref: new Model(
                    type: UserEntity::class,
                    groups: ['user_details']
                )
            )
        )
    )]
    public function list(Request $request): JsonResponse|View|Response
    {
        try {
            $this->denyAccessUnlessGranted('ROLE_ERP');
        } catch (\Exception $e) {
            return $this->respondAccessDenied($e->getMessage());
        }

        try {
            $this
                ->userValidator
                ->setAction('list')
                ->validate($request->query->all());
        } catch (ValidationException $e) {
            return $this->respondBadRequest($e->getMessage(), $e->getErrors());
        }

        $list = $this->userService->getList($request);

        return $this->getResponse($list);
    }

    #[Rest\View(serializerGroups: ['user_details'])]
    #[Route(path: '/change-status', name: 'change_status', methods: ['PATCH'])]
    #[OA\Tag(name: 'User')]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            required: ['id'],
            properties: [
                new OA\Property(property: 'id', type: 'integer'),
                new OA\Property(property: 'enabled', type: 'boolean'),
            ],
            type: 'object'
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns the success message.',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean'),
            ]
        )
    )]
    public function changeStatus(Request $request): JsonResponse|View|Response
    {
        $params = $request->toArray();
        try {
            $this->denyAccessUnlessGranted('ROLE_USER_ADMIN');
        } catch (\Exception $e) {
            return $this->respondAccessDenied($e->getMessage());
        }

        try {
            $this
                ->userValidator
                ->setAction('change_status')
                ->validate($params);
        } catch (ValidationException $e) {
            return $this->respondBadRequest($e->getMessage(), $e->getErrors());
        }

        try {
            $this->userService->changeStatus($request);
        } catch (\Exception $e) {
            return $this->respondInternalError($e->getMessage());
        }

        return $this->respondSuccess();
    }
}
