<?php

namespace Bermuda\Polyglot\Generator;

use Bermuda\Polyglot\PluralRule\PluralRuleProviderInterface;
use Bermuda\Polyglot\PluralRule\PluralCategory;
use Bermuda\Polyglot\PluralRule\PluralMap;

/**
 * Builder for plural messages
 */
class PluralBuilder
{
    private string $variable;
    private array $cases = [];
    private string $locale;
    private PluralRuleProviderInterface $pluralRuleProvider;

    public function __construct(string $variable, string $locale, PluralRuleProviderInterface $pluralRuleProvider)
    {
        $this->variable = $variable;
        $this->locale = $locale;
        $this->pluralRuleProvider = $pluralRuleProvider;
    }

    /**
     * Add a case for a specific plural category
     *
     * @param string $category Plural category (one, few, many, other, etc.)
     * @param string $text Text for this case
     * @return self For method chaining
     */
    public function when(string $category, string $text): self
    {
        $this->cases[$category] = $text;
        return $this;
    }

    /**
     * Add an 'other' case as fallback
     *
     * @param string $text Text for other cases
     * @return self For method chaining
     */
    public function otherwise(string $text): self
    {
        return $this->when('other', $text);
    }

    /**
     * Add cases for the most common plural forms for the current locale
     * using a base message and a set of inflection endings
     *
     * @param string $baseMessage Base message with # placeholder for the number
     * @param array<string, string> $endings Map of endings for different plural categories
     * @return self For method chaining
     */
    public function withInflections(string $baseMessage, array $endings = []): self
    {
        try {
            $rule = $this->pluralRuleProvider->getRule($this->locale);
            $language = substr($this->locale, 0, 2);

            $canonicalExamples = PluralMap::getCanonicalExamples($language);

            $categoryExamples = [];
            foreach ($canonicalExamples as $number) {
                $category = $rule->getCategory($number)->value;
                if (!isset($categoryExamples[$category])) {
                    $categoryExamples[$category] = $number;
                }
            }

            // Добавляем примеры для найденных категорий
            foreach ($categoryExamples as $category => $example) {
                if (!isset($this->cases[$category])) {
                    $message = str_replace('#', (string)$example, $baseMessage);

                    if (isset($endings[$category])) {
                        $message .= $endings[$category];
                    }

                    $this->cases[$category] = $message;
                }
            }

            if (!isset($this->cases[PluralCategory::OTHER->value]) && !empty($this->cases)) {
                $firstCategory = array_key_first($this->cases);
                $this->cases[PluralCategory::OTHER->value] = $this->cases[$firstCategory];
            }

        } catch (\Exception $e) {
            if (!isset($this->cases[PluralCategory::OTHER->value])) {
                $message = str_replace('#', '0', $baseMessage);
                if (isset($endings[PluralCategory::OTHER->value])) {
                    $message .= $endings[PluralCategory::OTHER->value];
                }

                $this->cases[PluralCategory::OTHER->value] = $message;
            }
        }

        return $this;
    }

    /**
     * Build the final Icu message
     *
     * @return string Icu message format
     */
    public function build(): string
    {
        $template = $this->variable.", plural, ";

        foreach ($this->cases as $category => $text) {
            $template .= $category."{" . $text . "} ";
        }

        return '{' . trim($template) . '}';
    }
}