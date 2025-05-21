<?php

declare(strict_types=1);

namespace Bermuda\Polyglot\Formatter;


use Bermuda\Polyglot\Exception\RuleNotFoundException;
use Bermuda\Polyglot\PluralRule\PluralRuleProviderInterface;

/**
 * ICU Message Formatter implementation
 * Uses native intl extension if available, with custom fallback implementation
 */
class IcuMessageFormatter implements MessageFormatterInterface
{
    protected bool $hasIntl;
    protected PluralRuleProviderInterface $pluralRuleProvider;

    public const string localeKey = '_locale';

    public function __construct(PluralRuleProviderInterface $pluralRuleProvider)
    {
        $this->pluralRuleProvider = $pluralRuleProvider;
        $this->hasIntl = extension_loaded('intl') && class_exists('MessageFormatter');
    }

    /**
     * @throws RuleNotFoundException
     */
    public function format(string $message, array $parameters): string
    {
        if ($this->hasIntl) {
            $locale = $parameters[self::localeKey] ?? 'en';

            $formatterParams = $parameters;
            unset($formatterParams[self::localeKey]);

            try {
                $adaptedMessage = $this->adaptMessageFormatForIntl($message);

                $formatter = new \MessageFormatter($locale, $adaptedMessage);
                $result = $formatter->format($formatterParams);

                if ($result !== false) {
                    return $result;
                }

            } catch (\Throwable) {
            }
        }

        return $this->formatWithCustomImplementation($message, $parameters);
    }
    
    protected function adaptMessageFormatForIntl(string $message): string
    {
        return $message;
    }


    /**
     * @throws RuleNotFoundException
     */
    protected function formatWithCustomImplementation(string $message, array $parameters): string
    {
        // Для простых параметров используем регулярные выражения
        if (!str_contains($message, ', ')) {
            return preg_replace_callback(
                '/\{([^{}]+)}/',
                static function($matches) use ($parameters) {
                    $key = trim($matches[1]);
                    return isset($parameters[$key]) ? (string)$parameters[$key] : $matches[0];
                },
                $message
            );
        }

        $tokens = $this->tokenize($message);
        return $this->processTokens($tokens, $parameters);
    }


    protected function tokenize(string $message): array
    {
        $tokens = [];
        $currentToken = '';
        $depth = 0;

        for ($i = 0; $i < strlen($message); $i++) {
            $char = $message[$i];

            if ($char === '{') {
                if ($depth === 0 && $currentToken !== '') {
                    $tokens[] = ['type' => 'text', 'value' => $currentToken];
                    $currentToken = '';
                }

                $depth++;
                $currentToken .= $char;
            } elseif ($char === '}') {
                $currentToken .= $char;
                $depth--;

                if ($depth === 0) {
                    $tokens[] = ['type' => 'expression', 'value' => $currentToken];
                    $currentToken = '';
                }
            } else {
                $currentToken .= $char;
            }
        }

        if ($currentToken !== '') {
            $tokens[] = ['type' => 'text', 'value' => $currentToken];
        }

        return $tokens;
    }

    /**
     * @throws RuleNotFoundException
     */
    protected function processTokens(array $tokens, array $parameters): string
    {
        $result = '';

        foreach ($tokens as $token) {
            if ($token['type'] === 'text') {
                $result .= $token['value'];
            } elseif ($token['type'] === 'expression') {
                $result .= $this->processExpression($token['value'], $parameters);
            }
        }

        return $result;
    }

    /**
     * @throws RuleNotFoundException
     */
    protected function processExpression(string $expression, array $parameters): string
    {
        $content = substr($expression, 1, -1);

        if (!str_contains($content, ',')) {
            $param = trim($content);
            return isset($parameters[$param]) ? (string)$parameters[$param] : $expression;
        }

        $parts = explode(',', $content, 3);

        if (count($parts) < 2) {
            return $expression;
        }

        $param = trim($parts[0]);
        $type = trim($parts[1]);
        $options = isset($parts[2]) ? trim($parts[2]) : '';

        if (!isset($parameters[$param])) {
            return $expression;
        }

        if ($type === 'plural') {
            return $this->processPluralExpression($param, $options, $parameters);
        } elseif ($type === 'select') {
            return $this->processSelectExpression($param, $options, $parameters);
        }

        return $expression;
    }

    /**
     * @throws RuleNotFoundException
     */
    protected function processPluralExpression(string $param, string $options, array $parameters): string
    {
        if (!isset($parameters[$param])) {
            return "\{$param}, plural, \{$options}}";
        }

        $count = (int)$parameters[$param];
        $locale = $parameters[self::localeKey] ?? 'en';

        $rule = $this->pluralRuleProvider->getRule($locale);
        $category = $rule->getCategory($count)->value;

        $forms = $this->parseOptions($options);

        if (!isset($forms[$category]) && isset($forms['other'])) {
            $category = 'other';
        }

        if (isset($forms[$category])) {
            $result = $forms[$category];

            $result = str_replace('#', (string)$count, $result);

            if (str_contains($result, '{')) {
                $result = $this->formatWithCustomImplementation($result, $parameters);
            }

            return $result;
        }

        return "\{$param}, plural, \{$options}}";
    }

    protected function processSelectExpression(string $param, string $options, array $parameters): string
    {
        if (!isset($parameters[$param])) {
            return "\{$param}, select, \{$options}}";
        }

        $value = (string)$parameters[$param];

        $choices = $this->parseOptions($options);

        $selected = null;
        if (isset($choices[$value])) {
            $selected = $choices[$value];
        } elseif (isset($choices['other'])) {
            $selected = $choices['other'];
        } else {
            return "\{$param}, select, \{$options}}";
        }

        if (str_contains($selected, '{')) {
            return $this->formatWithCustomImplementation($selected, $parameters);
        }

        return $selected;
    }

    protected function parseOptions(string $options): array
    {
        $result = [];
        $currentKey = '';
        $currentValue = '';
        $inKey = true;
        $braceDepth = 0;

        for ($i = 0; $i < strlen($options); $i++) {
            $char = $options[$i];

            if ($inKey) {
                if ($char === '{') {
                    $inKey = false;
                    $braceDepth = 1;
                } elseif (!ctype_space($char)) {
                    $currentKey .= $char;
                }
            } else {
                if ($char === '{') {
                    $braceDepth++;
                    $currentValue .= $char;
                } elseif ($char === '}') {
                    $braceDepth--;
                    if ($braceDepth === 0) {
                        $result[trim($currentKey)] = $currentValue;
                        $currentKey = '';
                        $currentValue = '';
                        $inKey = true;
                    } else {
                        $currentValue .= $char;
                    }
                } else {
                    $currentValue .= $char;
                }
            }
        }

        return $result;
    }
}