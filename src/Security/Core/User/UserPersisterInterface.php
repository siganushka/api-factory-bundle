<?php

declare(strict_types=1);

namespace Siganushka\ApiFactoryBundle\Security\Core\User;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @template TUser of UserInterface = UserInterface
 */
interface UserPersisterInterface
{
    /**
     * @return TUser
     */
    public function persist(array $credentials): UserInterface;
}
