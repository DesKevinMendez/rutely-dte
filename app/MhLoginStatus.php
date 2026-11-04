<?php

namespace App;

enum MhLoginStatus: string
{
    case AUTHENTICATED = 'authenticated';
    case REJECTED = 'rejected';
    case UNVERIFIED = 'unverified';
}
