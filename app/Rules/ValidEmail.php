<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\Extra\SpoofCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\NoRFCWarningsValidation;
use Egulias\EmailValidator\Validation\RFCValidation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Concerns\FilterEmailValidation;

/**
 * Validates an email address using additional checks beyond basic format validation.
 *
 * The following validations are applied:
 * - RFC Validation
 * - DNS Validation (skipped outside of production)
 */
class ValidEmail implements ValidationRule
{
    /** @var string[] $validations */
    protected array $validations = ['rfc', 'dns'];
    
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) && ! (is_object($value) && method_exists($value, '__toString'))) {
            $fail('The :attribute must be a valid email address.');

            return;
        }

        $validations = collect($this->validations)
            ->unique()
            ->map(function ($validation) {
                if ($validation === 'rfc') {
                    return new RFCValidation();
                } elseif ($validation === 'strict') {
                    return new NoRFCWarningsValidation();
                } elseif ($validation === 'dns' && app()->isProduction()) {
                    return new DNSCheckValidation();
                } elseif ($validation === 'spoof') {
                    return new SpoofCheckValidation();
                } elseif ($validation === 'filter') {
                    return new FilterEmailValidation();
                }

                return null;
            })
            ->values()
            ->filter()
            ->all() ?: [new RFCValidation()];

        if (! (new EmailValidator)->isValid((string) $value, new MultipleValidationWithAnd($validations))) {
            $fail('The :attribute must be a valid email address.');
        }
    }
}
