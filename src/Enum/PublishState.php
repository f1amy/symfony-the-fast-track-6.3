<?php

namespace App\Enum;

enum PublishState: string
{
    case Submitted = 'submitted';
    case Spam = 'spam';
    case Published = 'published';
}
