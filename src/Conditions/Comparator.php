<?php

declare(strict_types=1);

namespace Devvoh\Phorage\Conditions;

enum Comparator
{
    case equals;
    case not_equals;
    case is_null;
    case is_not_null;
    case less_than;
    case less_than_or_eq;
    case greater_than;
    case greater_than_or_eq;
}
