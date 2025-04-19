<?php

declare(strict_types=1);

namespace EnterpriseToolingForSymfony\SharedBundle\DateAndTime\Enum;

enum Timezone: string
{
    case UTC          = 'UTC';
    case EuropeBerlin = 'Europe/Berlin';
}
