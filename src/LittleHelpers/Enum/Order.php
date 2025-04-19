<?php

declare(strict_types=1);

namespace EnterpriseToolingForSymfony\SharedBundle\LittleHelpers\Enum;

enum Order: string
{
    case Ascending  = 'ASC';
    case Descending = 'DESC';
}
