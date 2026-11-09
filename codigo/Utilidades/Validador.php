<?php

namespace JERAM\Utilidades;

/**
 * Validación de formularios con reglas declarativas:
 *
 *   $v = new Validador();
 *   $v->validar($_POST, ['email' => ['required', 'email']]);
 *
 * Cada regla invoca un método privado validarXxx(); si algo falla
 * se acumula el error y primerError() lo lista para mostrarlo.
 */
class Validador
{
    private array $errores = [];
    private array $data = [];

    public function validar(array $data, array $reglas): bool
    {
        $this->data = $data;
        $this->errores = [];

        foreach ($reglas as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $label = $this->obtenerEtiqueta($field);

            foreach ($fieldRules as $rule => $param) {
                if (is_numeric($rule)) {
                    $rule = $param;
                    $param = null;
                }

                $ruleMethod = 'validar' . ucfirst($rule);
                if (method_exists($this, $ruleMethod)) {
                    $error = $this->$ruleMethod($field, $value, $param, $label);
                    if ($error) {
                        $this->errores[$field][] = $error;
                    }
                }
            }
        }

        return empty($this->errores);
    }

    public function errores(): array
    {
        return $this->errores;
    }

    public function primerError(): ?string
    {
        $first = reset($this->errores);
        return $first ? $first[0] : null;
    }

    public function tieneError(string $field): bool
    {
        return isset($this->errores[$field]);
    }

    public function obtenerErrores(string $field): array
    {
        return $this->errores[$field] ?? [];
    }

    private function obtenerEtiqueta(string $field): string
    {
        $labels = [
            'nombre' => 'El nombre',
            'email' => 'El correo electrónico',
            'password' => 'La contraseña',
            'password_confirmation' => 'La confirmación de contraseña',
            'contenido' => 'El comentario',
            'score' => 'La puntuación',
            'game_slug' => 'El juego',
            'titulo' => 'El título',
            'slug' => 'El slug',
            'descripcion_corta' => 'La descripción corta',
        ];
        return $labels[$field] ?? "El campo {$field}";
    }

    private function validarRequired(string $field, $value, $param, string $label): ?string
    {
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            return "{$label} es obligatorio";
        }
        return null;
    }

    private function validarEmail(string $field, $value, $param, string $label): ?string
    {
        if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return "{$label} no tiene un formato válido";
        }
        return null;
    }

    private function validarMin(string $field, $value, $param, string $label): ?string
    {
        if ($value !== null && $value !== '') {
            if (is_string($value) && mb_strlen($value) < (int)$param) {
                return "{$label} debe tener al menos {$param} caracteres";
            }
            if (is_numeric($value) && (int)$value < (int)$param) {
                return "{$label} debe ser al menos {$param}";
            }
        }
        return null;
    }

    private function validarMax(string $field, $value, $param, string $label): ?string
    {
        if ($value !== null && $value !== '') {
            if (is_string($value) && mb_strlen($value) > (int)$param) {
                return "{$label} no debe exceder {$param} caracteres";
            }
            if (is_numeric($value) && (int)$value > (int)$param) {
                return "{$label} no debe exceder {$param}";
            }
        }
        return null;
    }

    private function validarConfirmed(string $field, $value, $param, string $label): ?string
    {
        $confirmationField = $param ?: $field . '_confirmation';
        $confirmationValue = $this->data[$confirmationField] ?? null;
        if ($value !== $confirmationValue) {
            return "{$label} y su confirmación no coinciden";
        }
        return null;
    }

    private function validarUnique(string $field, $value, $param, string $label): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $parts = explode(',', $param);
        $table = $parts[0];
        $column = $parts[1] ?? $field;
        $excludeId = $parts[2] ?? null;
        $excludeColumn = $parts[3] ?? 'id';

        $pdo = \JERAM\Configuracion\BaseDeDatos::obtenerInstancia();
        $query = "SELECT id FROM {$table} WHERE {$column} = ?";
        $queryParams = [$value];

        if ($excludeId) {
            $query .= " AND {$excludeColumn} != ?";
            $queryParams[] = $excludeId;
        }

        $query .= " LIMIT 1";
        $stmt = $pdo->prepare($query);
        $stmt->execute($queryParams);

        if ($stmt->fetch()) {
            return "{$label} ya está en uso";
        }
        return null;
    }

    private function validarNumeric(string $field, $value, $param, string $label): ?string
    {
        if ($value !== null && $value !== '' && !is_numeric($value)) {
            return "{$label} debe ser un número";
        }
        return null;
    }

    private function validarInteger(string $field, $value, $param, string $label): ?string
    {
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_INT)) {
            return "{$label} debe ser un número entero";
        }
        return null;
    }
}