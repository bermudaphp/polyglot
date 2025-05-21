<?php

namespace Bermuda\Polyglot\Generator;

use Bermuda\Polyglot\PluralRule\PluralRuleProviderInterface;

/**
 * Builder for nested messages (used in callbacks)
 */
final class NestedMessageBuilder
{
    /**
     * Start building a plural message
     *
     * @param string $variable Variable name
     * @param string $locale Locale code
     * @param PluralRuleProviderInterface|null $pluralRuleProvider Optional custom rule provider
     * @return PluralBuilder Plural builder
     */
    public function plural(string $variable, string $locale, ?PluralRuleProviderInterface $pluralRuleProvider = null): PluralBuilder
    {
        return IcuMessage::plural($variable, $locale, $pluralRuleProvider);
    }

    /**
     * Start building a select message
     *
     * @param string $variable Variable name
     * @return SelectBuilder Select builder
     */
    public function select(string $variable): SelectBuilder
    {
        return IcuMessage::select($variable);
    }
}