<?php
declare(strict_types = 1)
;

const EMAIL_MAX_LENGTH = 320;

class StringErrors
{
    public ?bool $less_than_minlen;
    public ?bool $greater_than_maxlen;
    public ?bool $differs_from_tgtlen;
}

class NumericErrors
{
    public bool $not_numeric;
    public ?bool $less_than_minlen;
    public ?bool $greater_than_maxlen;
    public ?bool $differs_from_tgtlen;
}

class EmailErrors
{
    public bool $missing_at_symbol;
    public bool $is_empty;
    public bool $is_too_long;
}

class PostValidationResult

{
    /** @var string[] */
    public array $missing_required_params;

    /** @var array<string, StringErrors|NumericErrors|EmailErrors> Key: param name*/
    public array $errors;

    /** @var array<string, mixed> Key: param name */
    public array $sanitized_params;
}

/** @param array<string, string> $schema */
function validate_post(array $schema): PostValidationResult
{
    $to_return = new PostValidationResult;
    $to_return->missing_required_params = [];
    $to_return->errors = [];

    foreach ($schema as $param_def => $type_def) {
        // TODO(matteo): sanity checks on spaces before/after/in the middle of $param_def,
        // but also type_def
        $param_len = mb_strlen($param_def);
        $qmark = $param_def[$param_len - 1] == '?';

        $param_name = $qmark ? mb_substr($param_def, 0, $param_len - 1) : $param_def;
        if (!isset($_POST[$param_name])) {
            if (!$qmark) {
                $to_return->missing_required_params[] = $param_name;
            }
            continue;
        }

        $value = trim($_POST[$param_name]);
        $types = explode('|', $type_def);

        $found_match = false;
        foreach ($types as $type) {
            $type_plain_name = null;
            $minlen = null;
            $maxlen = null;
            $tgtlen = null;

            $paren_pos = strpos($type, '(');
            if ($paren_pos === false) {
                $type_plain_name = $type;
            }
            else {
                $type_plain_name = mb_substr($type, 0, $paren_pos);
                $type_params_raw = mb_substr($type, $paren_pos + 1, mb_strlen($type) - 3);
                assert($type_params_raw !== "");
                $params = explode(',', $type_params_raw);
                $params_count = count($params);
                assert($params_count <= 2);

                if (count($params) == 1) {
                    $tgtlen = intval($params[0]);
                }
                else {
                    $minlen = intval($params[0]);
                    $maxlen = intval($params[1]);
                }
            }


            switch ($type_plain_name) {
                case 'string': {
                        $errs = new StringErrors;
                        $errs->less_than_minlen = null;
                        $errs->greater_than_maxlen = null;
                        $errs->differs_from_tgtlen = null;

                        $value_len = mb_strlen($value);
                        if (!is_null($minlen)) {
                            $errs->less_than_minlen = ($value_len < $minlen);
                        }

                        if (!is_null($maxlen)) {
                            $errs->greater_than_maxlen = ($value_len > $maxlen);
                        }

                        if (!is_null($tgtlen)) {
                            $errs->differs_from_tgtlen = ($value_len != $tgtlen);
                        }

                        if ($errs->less_than_minlen || $errs->greater_than_maxlen || $errs->differs_from_tgtlen) {
                            $to_return->errors[$param_name] = $errs;
                        }
                        $to_return->sanitized_params[$param_name] = $value;
                    }
                    break;

                case 'numeric': {
                        $value_len = mb_strlen($value);

                        $errs = new NumericErrors;
                        $errs->not_numeric = ($value_len > 0) && !ctype_digit($value);
                        $errs->less_than_minlen = null;
                        $errs->greater_than_maxlen = null;
                        $errs->differs_from_tgtlen = null;

                        if (!is_null($minlen)) {
                            $errs->less_than_minlen = ($value_len < $minlen);
                        }

                        if (!is_null($maxlen)) {
                            $errs->greater_than_maxlen = ($value_len > $maxlen);
                        }

                        if (!is_null($tgtlen)) {
                            $errs->differs_from_tgtlen = ($value_len != $tgtlen);
                        }

                        if ($errs->not_numeric || $errs->less_than_minlen || $errs->greater_than_maxlen || $errs->differs_from_tgtlen) {
                            $to_return->errors[$param_name] = $errs;
                        }
                        $to_return->sanitized_params[$param_name] = $value;
                    }
                    break;

                case 'email': {
                        $errs = new EmailErrors;
                        $errs->missing_at_symbol = (strpos($value, '@') === false);
                        $errs->is_empty = (strlen($value) === 0);
                        $errs->is_too_long = (mb_strlen($value) > EMAIL_MAX_LENGTH);

                        if ($errs->missing_at_symbol || $errs->is_empty || $errs->is_too_long) {
                            $to_return->errors[$param_name] = $errs;
                        }
                        $to_return->sanitized_params[$param_name] = $value;
                    }
                    break;

                default: {
                        throw new Exception('impossible case');
                    }
            }
        }
    }
    return $to_return;
}

/**
 * Connessione al DB Appane
 */
function connectToDb(): PDO
{
    $host = 'localhost';
    $db = 'appane_parodi';
    $user = 'quintae';
    $pass = 'Qu!nta'; // Modificare in base all'utente MySQL. Per XAMPP di default è vuoto.

    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        return new PDO($dsn, $user, $pass, $options);
    }
    catch (PDOException $e) {
        die("Connessione fallita: " . $e->getMessage());
    }
}
