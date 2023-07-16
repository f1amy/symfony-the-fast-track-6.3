<?php

namespace App\Enum;

enum PublishState: string
{
    case Submitted = 'submitted';
    case Ham = 'ham';
    case PotentialSpam = 'potential_spam';
    case Spam = 'spam';
    case Published = 'published';
    case Ready = 'ready';
    case Rejected = 'rejected';
}
