<?php

declare(strict_types=1);

namespace Siganushka\ApiFactoryBundle\Security\Core\User;

use Symfony\Component\Security\Core\User\UserInterface;

class NullUserPersister implements UserPersisterInterface
{
    public function persist(string $userIdentifier, array $attributes): ?UserInterface
    {
        return null;
    }
}
