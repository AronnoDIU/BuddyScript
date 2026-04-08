<?php

namespace AdminBundle\Controller;

use CoreBundle\Entity\User as UserEntity;
use CoreBundle\Service\User as UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class UserAdminController extends BaseAdminController
{
    private readonly UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function copyRolesAction(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            try {
                $this->userService->copyRoles($request);
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }

            $this->addFlash('success', 'Roles copied successfully.');
        }

        return $this->render('admin/user/copy_roles.html.twig', [
            'users' => $em->getRepository(UserEntity::class)->findBy(['enabled' => true]),
        ]);
    }

    public function toggleEnabledAction(Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $id = $request->get($this->admin->getIdParameter());
        $user = $this->admin->getObject($id);

        if (!$user) {
            $this->addFlash('sonata_flash_error', 'User not found');

            return new RedirectResponse($this->admin->generateUrl('list'));
        }

        $user->setEnabled(!$user->isEnabled());
        $em->persist($user);
        $em->flush();

        $this->addFlash(
            'sonata_flash_success',
            \sprintf(
                'User %s has been %s',
                $user->getUsername(),
                $user->isEnabled() ? 'enabled' : 'disabled'
            )
        );

        return new RedirectResponse($this->admin->generateUrl('list'));
    }
}
