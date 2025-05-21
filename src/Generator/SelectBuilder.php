<?php

namespace Bermuda\Polyglot\Generator;

/**
 * Builder for select messages
 */
class SelectBuilder
{
    private string $variable;
    private array $cases = [];

    public function __construct(string $variable)
    {
        $this->variable = $variable;
    }

    /**
     * Add a case for a specific value
     *
     * @param string $value Value to match
     * @param callable|string $text Text or nested builder callback
     * @return self For method chaining
     */
    public function when(string $value, callable|string $text): self
    {
        if (is_callable($text)) {
            $nestedBuilder = new NestedMessageBuilder();
            $result = $text($nestedBuilder);

            // Проверяем тип результата
            if ($result instanceof PluralBuilder || $result instanceof SelectBuilder) {
                $text = $result->build();
            } elseif (is_string($result)) {
                $text = $result;
            } else {
                // Если результат не строка и не поддерживаемый билдер, выбрасываем исключение
                throw new \InvalidArgumentException(
                    'Callback for SelectBuilder::when() must return a string or a builder instance'
                );
            }
        }

        $this->cases[$value] = $text;
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
     * Build the final Icu message
     *
     * @return string Icu message format
     */
    public function build(): string
    {
        $template = $this->variable.", select, ";

        foreach ($this->cases as $value => $text) {
            $template .= $value."{" . $text . "} ";
        }

        return '{' . trim($template) . '}';
    }
}