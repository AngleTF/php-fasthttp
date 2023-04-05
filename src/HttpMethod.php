<?php

namespace angletf;

enum HttpMethod: int
{
    case GET = 1;
    case POST = 2;
    case JSON = 3;
    case FILE = 4;
}