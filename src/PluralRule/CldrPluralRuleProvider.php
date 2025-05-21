<?php

namespace Bermuda\Polyglot\PluralRule;


use Bermuda\Polyglot\Exception\RuleNotFoundException;
use Bermuda\Polyglot\Locale;
use Bermuda\Polyglot\LocaleEnum;

/**
 * Default implementation of the plural rule provider using CLDR rules
 */
class CldrPluralRuleProvider implements PluralRuleProviderInterface
{
    /** @var array<string, PluralRule> */
    private array $rules = [];

    public function __construct(array $rules = [])
    {
        if (!empty($rules)) {
            foreach ($rules as $rule) $this->addRule($rule);
        }
    }

    /**
     * @param string|Locale $locale
     * @return PluralRule
     * @throws RuleNotFoundException
     */
    public function getRule(string|LocaleEnum $locale): PluralRule
    {
        if ($locale instanceof LocaleEnum) $locale = $locale->getLanguageCode();
        $normalizedLocale = strtolower(substr($locale, 0, 2));

        if (isset($this->rules[$normalizedLocale])) {
            return $this->rules[$normalizedLocale];
        }

        $rule = PluralRule::forLocale($normalizedLocale);

        if (!$rule) {
            throw new RuleNotFoundException('No rule found for ' . $locale);
        }

        return $rule;
    }

    private function addRule(PluralRule $rule): void
    {
        $this->rules[] = $rule;
    }

}