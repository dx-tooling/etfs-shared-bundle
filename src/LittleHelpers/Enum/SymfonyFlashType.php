<?php

declare(strict_types=1);

namespace EnterpriseToolingForSymfony\SharedBundle\LittleHelpers\Enum;

enum SymfonyFlashType: string
{
    case DANGER  = 'danger';
    case WARNING = 'warning';
    case INFO    = 'info';
    case SUCCESS = 'success';
}
