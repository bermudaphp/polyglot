<?php

namespace Bermuda\Polyglot\PluralRule;

/**
 * Enum for plural categories
 */
enum PluralCategory: string
{
    case ZERO = 'zero';
    case ONE = 'one';
    case TWO = 'two';
    case FEW = 'few';
    case MANY = 'many';
    case OTHER = 'other';
}