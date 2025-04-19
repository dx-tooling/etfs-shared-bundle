<?php

declare(strict_types=1);

namespace EnterpriseToolingForSymfony\SharedBundle\DateAndTime\Enum;

enum Format: string
{
    case ISO8601              = 'Y-m-d\TH:i:s.uP';
    case DATABASE_DATETIME    = 'Y-m-d H:i:s';
    case RFC_3339_SECTION_5_6 = 'Y-m-d\TH:i:s\Z';
}
