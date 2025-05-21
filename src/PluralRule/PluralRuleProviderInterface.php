<?php

namespace Bermuda\Polyglot\PluralRule;

use Bermuda\Polyglot\Exception\RuleNotFoundException;
use Bermuda\Polyglot\LocaleEnum;

/**
 * Interface for plural rule providers
 */
interface PluralRuleProviderInterface
{
    /**
     * Get the plural rule for a locale
     * @throws RuleNotFoundException
     */
    public function getRule(string|LocaleEnum $locale): PluralRule;
}