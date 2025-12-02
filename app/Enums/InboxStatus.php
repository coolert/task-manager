<?php

namespace App\Enums;

enum InboxStatus: string
{
    case SUCCESS = 'success';
    case FAILED  = 'failed';
    case SKIPPED = 'skipped';
}
