<?php

namespace Sift;

use Illuminate\Support\Arr;

class Sift
{
    private $data;
    private $lastValidator;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function query(array $query): array
    {
        if (!count($query)) {
            $query = [true];
        }

        if (!$this->isAssoc($query)) {
            $query = ['$and' => $query];
        }

        $this->lastValidator = $validators = [$this->createValidator($query)];

        return $this->doQuery($validators, $this->data);
    }

    public function getLastValidator()
    {
        return $this->lastValidator;
    }

    private function doQuery($validators, $data): array
    {
        $result = [];
        foreach ($data as $item) {
            if ($this->validate($validators, $item)) {
                $result[] = $item;
            }
        }

        return $result;
    }

    private function validate($validators, $item)
    {
        if (empty($validators)) {
            return true;
        }

        $result = [];
        foreach ($validators as $validator) {
            $result[] = $this->validateOne($validator, $item);
        }

        return Expression::fn_and($result, true);
    }

    private function validateOne($validator, $item)
    {
        if (!array_key_exists('validator', $validator)) {
            return false;
        }

        if (!array_key_exists('key', $validator)) {
            return false;
        }

        $result = false;
        $fn = $validator['validator'];
        if (is_callable($fn)) {
            $key = $validator['key'];
            $validatorValue = $validator['value'];
            if (is_array($validatorValue) && is_array($validatorValue[0])) {
                $validatorValue = [];
                foreach ($validator['value'] as $subValidator) {
                    $validatorValue[] = $this->validateOne($subValidator, $item);
                }
            }

            $value = Arr::get($item, $key);
            $x = call_user_func_array($fn, [$validatorValue, $value, $key, $item]);
            $result = $x;
        }

        return $result;
    }

    private function createValidator($query)
    {
        if (!is_array($query)) {
            return $query;
        }

        foreach ($query as $qop => $qex) {
            // $qop is an integer index of the array
            // $qop is an expression
            if (Expression::has($qop)) {
                $expValue = $qex;
                if (is_array($qex) && !$this->isAssoc($qex)) {
                    $expValue = [];
                    foreach ($qex as $subQuery) {
                        $expValue[] = $this->createValidator($subQuery);
                    }
                } elseif (is_array($qex)) {
                    // nested expression
                    $expValue = $this->createValidator($qex);
                }

                $validators = [
                    'validator' => Expression::fn($qop),
                    'value' => $expValue,
                    'key' => $qop,
                ];
            } else { // $qop is a field name
                if (is_array($qex) && $this->isAssoc($qex)) { // query expression is an associative array
                    $parsedQueryExpression = $this->createValidator($qex);
                } else {
                    $parsedQueryExpression = $this->createValidator(['$eq' => $qex]);
                }

                $parsedQueryExpression['key'] = $qop;

                $validators = $parsedQueryExpression;
            }
        }

        return $validators;
    }

    private function isAssoc(array $arr): bool
    {
        if (array() === $arr) {
            return false;
        }

        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}

