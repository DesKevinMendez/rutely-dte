<?php

namespace App;

enum Environment: string
{
    case SANDBOX = '00';
    case PRODUCTION = '01';
}
