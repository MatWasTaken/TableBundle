<?php

namespace Kilik\TableBundle\Components;

class Filter
{
    /**
     * Type d'input.
     */
    const FILTER_TYPE = 'text';

/**
 * Filter type.
 */
    // WHERE field LIKE '%value%'
    const TYPE_LIKE = 'like';
    // WHERE field NOT LIKE '%value%'
    const TYPE_NOT_LIKE = '!';
    // WHERE field LIKE 'value'
    const TYPE_EQUAL = '=';
    // WHERE field != 'value'
    const TYPE_NOT_EQUAL = '!=';
    // WHERE field = 'value'
    const TYPE_EQUAL_STRICT = '==';
    // WHERE field > 'value'
    const TYPE_GREATER = '>';
    // WHERE field >= 'value'
    const TYPE_GREATER_OR_EQUAL = '>=';
    // WHERE field < 'value'
    const TYPE_LESS = '<';
    // WHERE field <= 'value'
    const TYPE_LESS_OR_EQUAL = '<=';
    // use input to apply arithmetic comparators, then filter the results
    const TYPE_AUTO = 'auto';
    const TYPES
        = [
            self::TYPE_LIKE,
            self::TYPE_NOT_LIKE,
            self::TYPE_EQUAL,
            self::TYPE_NOT_EQUAL,
            self::TYPE_EQUAL_STRICT,
            self::TYPE_GREATER,
            self::TYPE_GREATER_OR_EQUAL,
            self::TYPE_LESS,
            self::TYPE_LESS_OR_EQUAL,
            self::TYPE_AUTO,
        ];
    const TYPE_DEFAULT = self::TYPE_AUTO;
    // specials types:
    const TYPE_NULL = 'null';
    const TYPE_NOT_NULL = 'not_null';
    const TYPE_IN = 'in';
    const TYPE_NOT_IN = 'not_in';

    /**
     * data formats.
     */
    const FORMAT_DATE = 'date';
    const FORMAT_TEXT = 'text';
    const FORMAT_DEFAULT = self::FORMAT_TEXT;
    const FORMATS = [self::FORMAT_DATE, self::FORMAT_TEXT];

    /**
     * Filter name.
     *
     * @var string
     */
    private $name;

    /**
     * Filter field.
     *
     * @var string
     */
    private $field;

    /**
     * This filter is a HAVING constraint ?
     *
     * @var bool
     */
    private $having = false;

    /**
     * Filter type.
     *
     * @var string
     */
    private $type = self::TYPE_DEFAULT;

    /**
     * Data format.
     *
     * @var string
     */
    private $dataFormat = self::FORMAT_DEFAULT;

    /**
     * Custom inputFormatter.
     *
     * @var callable
     *
     * prototype (Filter,$defaultOperator,$value)
     */
    private $inputFormatter = null;

    /**
     * Default filter value (forced from GET VARS for example).
     *
     * @var string
     */
    private $defaultValue = null;

    /**
     * Set the filter name.
     *
     * @param string $name
     *
     * @return static
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the filter name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the filter field (used in a query).
     *
     * @param string $field
     *
     * @return static
     */
    public function setField($field)
    {
        $this->field = $field;

        return $this;
    }

    /**
     * Get the filter field (user in a query).
     *
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Set the filter type.
     *
     * @param string $type
     *
     * @return static
     */
    public function setType($type)
    {
        if (!in_array($type, self::TYPES)) {
            throw new \InvalidArgumentException("bad type {$type}");
        }
        $this->type = $type;

        return $this;
    }

    /**
     * Get the filter type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set if this filter is working on a HAVING clause, or not.
     *
     * @param bool $having
     *
     * @return static
     */
    public function setHaving($having)
    {
        $this->having = $having;

        return $this;
    }

    /**
     * Get if this filter is working on a HAVING clause, or not.
     *
     * @return string
     */
    public function getHaving()
    {
        return $this->having;
    }

    /**
     * Set the data format converter (from user input to sql value).
     *
     * @param string $dataFormat
     *
     * @return static
     */
    public function setDataFormat($dataFormat)
    {
        if (!in_array($dataFormat, static::FORMATS)) {
            throw new \InvalidArgumentException("bad format '{$dataFormat}'");
        }
        $this->dataFormat = $dataFormat;

        return $this;
    }

    /**
     * Get the data format.
     *
     * @return string
     */
    public function getDataFormat()
    {
        return $this->dataFormat;
    }

    /**
     * Get the operator and the value of an input string.
     *
     * @param string $input
     *
     * @return array [string operator,string value]
     */
    public function getOperatorAndValue($input)
    {
        switch ($this->getType()) {
            case self::TYPE_GREATER:
            case self::TYPE_GREATER_OR_EQUAL:
            case self::TYPE_LESS:
            case self::TYPE_LESS_OR_EQUAL:
            case self::TYPE_NOT_LIKE:
            case self::TYPE_LIKE:
            case self::TYPE_NOT_EQUAL:
            case self::TYPE_EQUAL_STRICT:
                return [$this->getType(), $input];
            case self::TYPE_AUTO:
            default:
                if ($input != false) {
                    $simpleOperator = substr($input, 0, 1);
                    $doubleOperator = substr($input, 0, 2);
                    // if start with operators
                    switch ($doubleOperator) {
                        case self::TYPE_GREATER_OR_EQUAL:
                        case self::TYPE_LESS_OR_EQUAL:
                        case self::TYPE_NOT_EQUAL:
                        case self::TYPE_EQUAL_STRICT:
                            return [$doubleOperator, substr($input, 2)];
                            break;
                        default:
                            switch ($simpleOperator) {
                                case self::TYPE_GREATER:
                                case self::TYPE_LESS:
                                case self::TYPE_EQUAL:
                                case self::TYPE_NOT_LIKE:
                                    return [$simpleOperator, substr($input, 1)];
                                    break;
                                default:
                                    return [self::TYPE_LIKE, $input];
                                    break;
                            }
                            break;
                    }
                    break;
                } else {
                    return [self::TYPE_LIKE, false];
                }
        }
    }

    /**
     * Set the custom formatter input.
     *
     * @param callable $formatter
     *
     * @return static
     */
    public function setInputFormatter($formatter)
    {
        $this->inputFormatter = $formatter;

        return $this;
    }

    /**
     * Get formatted input.
     *
     * @param string $operator
     * @param string $input
     *
     * @return array searchOperator, formatted input
     */
    public function getFormattedInput($operator, $input)
    {
        // if we use custom formatter
        if (is_callable($this->inputFormatter)) {
            $function = $this->inputFormatter;

            return $function($this, $operator, $input);
        } // else, use standard input converter
        else {
            switch ($this->getDataFormat()) {
                // date/time format dd/mm/YYYY HH:ii:ss
                case self::FORMAT_DATE:
                    $params = explode('/', str_replace(['-', ' '], '/', $input));
                    // only year ?
                    if (count($params) == 1) {
                        $fInput = $params[0];
                    } // month/year ?
                    elseif (count($params) == 2) {
                        $fInput = sprintf('%04d-%02d', $params[1], $params[0]);
                    } // day/month/year ?
                    elseif (count($params) == 3) {
                        $fInput = sprintf('%04d-%02d-%02d', $params[2], $params[1], $params[0]);
                    } // day/month/year hour ?
                    elseif (count($params) == 4) {
                        $fInput = sprintf('%04d-%02d-%02d %02d', $params[3], $params[2], $params[1], $params[0]);
                    } // day/month/year hour:minute ?
                    elseif (count($params) == 5) {
                        $fInput = sprintf(
                            '%04d-%02d-%02d %02d:%02d',
                            $params[4],
                            $params[3],
                            $params[2],
                            $params[1],
                            $params[0]
                        );
                    } // day/month/year hour:minute ?
                    elseif (count($params) == 6) {
                        $fInput = sprintf(
                            '%04d-%02d-%02d %02d:%02d:%02d',
                            $params[5],
                            $params[4],
                            $params[3],
                            $params[2],
                            $params[1],
                            $params[0]
                        );
                    } // default, same has raw value
                    else {
                        $fInput = $input;
                    }
                    break;
                case self::FORMAT_TEXT:
                default:
                    $fInput = $input;
                    break;
            }
        }

        return [$operator, $fInput];
    }

    /**
     * Set Default value
     *
     * @param string $defaultValue
     *
     * @return static
     */
    public function setDefaultValue($defaultValue)
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }

    /**
     * Get default value
     *
     * @return string
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }
}
