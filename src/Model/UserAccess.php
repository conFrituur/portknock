<?php

namespace Portknock\Model;

enum UserAccess
{
    case READ_ONLY;
    case WRITE_ONLY;
}
