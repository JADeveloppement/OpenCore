<?php

/**
 * Nom du Fichier : Validator.php
 * Projet : JADCoreEngine
 * @category   Framework
 * @package    JADeveloppement
 * @author     Jalal AISSAOUI <jalal.aissaoui@outlook.com>
 * @copyright  2024-2026 JADeveloppement
 * @license    https://opensource.org/licenses/MIT MIT License
 * @link       https://jadeveloppement.fr
 */

namespace Config\Facades\Validation;

class Validator
{
    protected array $errors = [];

    public function validate(array $data, array $rules): bool
    {
        foreach ($rules as $field => $fieldRules) {
            $rulesArray = explode('|', $fieldRules);
            foreach ($rulesArray as $rule) {
                $this->applyRule($field, $data[$field] ?? null, $rule);
            }
        }
        return empty($this->errors);
    }

    protected function applyRule($field, $value, $rule)
    {
        if (empty($value) && $rule !== 'required') {
            return;
        }

        switch ($rule) {
            case 'text':
                if (!is_string($value)) {
                    $this->errors[$field][] = "Le champ {$field} doit être du texte.";
                }
                break;

            case 'number':
                if (!is_numeric($value)) {
                    $this->errors[$field][] = "Le champ {$field} doit être un nombre.";
                }
                break;

            case 'date':
                if (!$this->validateDateTime($value, 'Y-m-d')) {
                    $this->errors[$field][] = "Le champ {$field} doit être une date au format AAAA-MM-JJ (ex: 2026-01-26).";
                }
                break;

            case 'timestamp':
                if (!$this->validateDateTime($value, 'Y-m-d H:i:s')) {
                    $this->errors[$field][] = "Le champ {$field} doit être un timestamp au format AAAA-MM-JJ HH:MM:SS.";
                }
                break;

            case 'required':
                if (empty($value)) {
                    $this->errors[$field][] = "Le champ {$field} est obligatoire.";
                }
                break;

            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->errors[$field][] = "Le format de l'email est invalide.";
                }
                break;
            case 'boolean':
                $acceptable = [true, false, 1, 0, '1', '0', 'true', 'false'];
                if (!in_array($value, $acceptable, true)) {
                    $this->errors[$field][] = "Le champ {$field} doit être un booléen.";
                }
                break;
        }

        if (str_contains($rule, ':')) {
            [$ruleName, $ruleValue] = explode(':', $rule);
            $ruleValue = (int) $ruleValue;
            $length = mb_strlen((string) $value);

            if ($ruleName === 'min' && $length < $ruleValue) {
                $msg = is_numeric($value) ? "doit être au moins {$ruleValue} caractères." : "doit contenir au moins {$ruleValue} caractères.";
                $this->errors[$field][] = "Le champ {$field} {$msg}";
            }

            if ($ruleName === 'max' && $length > $ruleValue) {
                $msg = is_numeric($value) ? "ne doit pas dépasser {$ruleValue} caractères." : "ne doit pas dépasser {$ruleValue} caractères.";
                $this->errors[$field][] = "Le champ {$field} {$msg}";
            }
        }
    }

    private function validateDateTime($value, $format): bool
    {
        $d = \DateTime::createFromFormat($format, $value);
        return $d && $d->format($format) === $value;
    }

    public function errors(): array
    {
        return $this->errors;
    }
}