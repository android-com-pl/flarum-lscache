<?php

namespace ACPL\FlarumLSCache;

enum LiteSpeedServerType: string
{
    case ADC = 'LiteSpeed ADC';
    case OPEN_LITESPEED = 'OpenLiteSpeed';
    case LITESPEED = 'LiteSpeed Web Server';
    case NONE = 'None';
}
