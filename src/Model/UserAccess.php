<?php

namespace Portknock\Model;

enum UserAccess: string
{
    case READ_ONLY = "READ_ONLY";
    case WRITE_ONLY = "WRITE_ONLY";
}
