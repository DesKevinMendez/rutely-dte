<?php

namespace App\Http\Requests\Dte;

use App\Environment;
use App\IssuableDteType;
use App\Models\Company;
use App\Models\Departament;
use App\Models\District;
use App\Models\DocumentType;
use App\Models\Dte;
use App\Models\EconomicActivity;
use App\Models\EstablishmentTypes;
use App\Models\ItemType;
use App\Models\Municipalities;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\Dte\Documents\DteDocumentBuilder;
use App\Services\Dte\Documents\TaxCreditBuilder;
use App\Services\Dte\MhCatalog;
use App\Services\Dte\TaxIdentifier;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Limits and required fields come from the official schemas: fe-f-v2, fe-ccf-v4 and fe-fse-v2.
 */
class StoreDteRequest extends FormRequest
{
    private const MAX_AMOUNT = 100000000000;

    /** Normativa 2.0, regla 7.3: Hacienda accepts ±$0.01 against its own calculation. */
    private const MH_TOLERANCE = 0.01;

    /** Normativa 2.0, Anexo IV: consumer data required above the legal amount. */
    private const IDENTIFICATION_FIELDS = [
        'tipoDocumento' => 'tipo de documento',
        'numDocumento' => 'número de documento',
        'nombre' => 'nombre',
    ];

    public function authorize(): bool
    {
        /** @var User|Company|null $principal */
        $principal = $this->user();

        if ($principal instanceof Company) {
            return $principal->tokenCan('create:dte');
        }

        return $principal?->can('create', Dte::class) ?? false;
    }

    /**
     * NIT, NRC and DUI are written with hyphens in El Salvador, but Hacienda receives only their digits,
     * and the NRC without leading zeros (Normativa 2.0, Anexo IV #41).
     */
    protected function prepareForValidation(): void
    {
        $receiver = $this->input('receptor');

        if (! is_array($receiver)) {
            return;
        }

        if (is_string($receiver['nit'] ?? null)) {
            $receiver['nit'] = str_replace('-', '', $receiver['nit']);
        }

        if (is_string($receiver['nrc'] ?? null)) {
            $receiver['nrc'] = TaxIdentifier::nrc($receiver['nrc']);
        }

        $hasDigitsOnlyDocument = in_array($receiver['tipoDocumento'] ?? null, [MhCatalog::DUI_DOCUMENT_TYPE, MhCatalog::NIT_DOCUMENT_TYPE], true);

        if ($hasDigitsOnlyDocument && is_string($receiver['numDocumento'] ?? null)) {
            $receiver['numDocumento'] = str_replace('-', '', $receiver['numDocumento']);
        }

        $this->merge(['receptor' => $receiver]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'tipoDte' => ['bail', 'sometimes', 'string', Rule::enum(IssuableDteType::class)],
            'ambiente' => ['sometimes', 'string', Rule::enum(Environment::class)],
            'emisor' => ['sometimes', 'array'],
            'emisor.nit' => ['sometimes', 'string', 'regex:/^(?:\d{14}|\d{4}-\d{6}-\d{3}-\d)$/'],
            'emisor.nrc' => ['sometimes', 'nullable', 'string'],
            'emisor.nombre' => ['sometimes', 'string', 'min:3'],
            'emisor.codActividad' => ['sometimes', 'string', 'size:5'],
            'emisor.descActividad' => ['sometimes', 'string', 'min:3'],
            'emisor.nombreComercial' => ['sometimes', 'nullable', 'string'],
            'emisor.tipoEstablecimiento' => ['sometimes', 'string', Rule::exists(EstablishmentTypes::class, 'code')],
            'emisor.direccion' => ['sometimes', 'array'],
            'emisor.direccion.departamento' => ['required_with:emisor.direccion', 'string', 'size:2'],
            'emisor.direccion.municipio' => ['required_with:emisor.direccion', 'string', 'size:2'],
            'emisor.direccion.complemento' => ['required_with:emisor.direccion', 'string', 'min:3'],
            'emisor.telefono' => ['sometimes', 'nullable', 'string'],
            'emisor.correo' => ['sometimes', 'nullable', 'email'],
            ...$this->receiverRules(),
            'items' => ['required', 'array', 'min:1', 'max:2000'],
            'items.*.descripcion' => ['required', 'string', 'max:1500'],
            'items.*.cantidad' => ['required', ...$this->amountRules(8), 'gt:0'],
            'items.*.precioUni' => ['required', ...$this->amountRules(8)],
            'items.*.montoDescu' => ['sometimes', ...$this->amountRules(8)],
            'items.*.tipoItem' => ['bail', 'sometimes', 'integer', Rule::exists(ItemType::class, 'code'), Rule::notIn([MhCatalog::OTHER_TAXES_ITEM_TYPE])],
            'items.*.codigo' => ['sometimes', 'nullable', 'string', 'max:25'],
            'items.*.uniMedida' => ['bail', 'sometimes', 'integer', Rule::in(MhCatalog::UNITS_OF_MEASURE)],
            ...$this->summaryRules(),
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $builder = DteDocumentBuilder::for($this->documentType());
                $totalPurchases = 0.0;

                foreach ($this->input('items') as $index => $item) {
                    $lineAmount = $builder->lineAmount($item);
                    $totalPurchases += $lineAmount;

                    if ($lineAmount < 0) {
                        $validator->errors()->add(
                            "items.{$index}.montoDescu",
                            'El monto de descuento del ítem no puede ser mayor que el valor de la línea.',
                        );
                    }

                    if ($this->isServiceWithAnotherUnit($item)) {
                        $validator->errors()->add(
                            "items.{$index}.uniMedida",
                            'Los servicios deben usar la unidad de medida 99 (Otra).',
                        );
                    }
                }

                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if ($builder->requiresReceiverIdentification($this->only(['items', 'resumen']))) {
                    foreach (self::IDENTIFICATION_FIELDS as $field => $label) {
                        if (blank($this->input("receptor.{$field}"))) {
                            $validator->errors()->add(
                                "receptor.{$field}",
                                "El {$label} del receptor es obligatorio en operaciones de $25,000.00 o más.",
                            );
                        }
                    }
                }

                if ($builder instanceof TaxCreditBuilder) {
                    $this->validatePerceivedIva($validator, $builder);
                }

                $isExcludedSubject = $this->documentType() === IssuableDteType::EXCLUDED_SUBJECT;

                if ($isExcludedSubject && (float) $this->input('resumen.descu', 0) > round($totalPurchases, 2)) {
                    $validator->errors()->add('resumen.descu', 'El descuento global no puede ser mayor que el total de compras.');

                    return;
                }

                $amountDue = $builder->totalToPay($this->only(['items', 'resumen']));

                if ($amountDue < 0) {
                    $validator->errors()->add('resumen', 'Las retenciones no pueden ser mayores que el total de la operación.');

                    return;
                }

                $condition = (int) $this->input('resumen.condicionOperacion', MhCatalog::CASH_CONDITION);

                if ($amountDue === 0.0 && $condition !== MhCatalog::CASH_CONDITION) {
                    $validator->errors()->add('resumen.condicionOperacion', 'La condición de la operación debe ser 1 (Contado) cuando el total a pagar es $0.00.');

                    return;
                }

                $payments = $this->input('resumen.pagos');
                $isExcludedSubjectOnCredit = $isExcludedSubject && $condition === MhCatalog::CREDIT_CONDITION;

                if (is_array($payments) && ! $isExcludedSubjectOnCredit) {
                    $paid = array_sum(array_column($payments, 'montoPago'));

                    if (round(abs($paid - $amountDue), 2) > self::MH_TOLERANCE) {
                        $validator->errors()->add('resumen.pagos', 'La suma de las formas de pago debe coincidir con el total a pagar.');
                    }
                }
            },
        ];
    }

    /**
     * Normativa 2.0, Anexo IV #141: when it applies, the perceived IVA is 1% of the taxed sales.
     */
    private function validatePerceivedIva(Validator $validator, TaxCreditBuilder $builder): void
    {
        $perceivedIva = (float) $this->input('resumen.ivaPerci', 0);
        $expectedIva = $builder->perceivedIva($this->only(['items', 'resumen']));

        if ($perceivedIva > 0 && round(abs($perceivedIva - $expectedIva), 2) > self::MH_TOLERANCE) {
            $validator->errors()->add(
                'resumen.ivaPerci',
                'El IVA percibido debe ser el 1% de las ventas gravadas: $'.number_format($expectedIva, 2).'.',
            );
        }
    }

    /** @param  array<string, mixed>  $item */
    private function isServiceWithAnotherUnit(array $item): bool
    {
        $itemType = (int) ($item['tipoItem'] ?? MhCatalog::SERVICE_ITEM_TYPE);
        $unit = (int) ($item['uniMedida'] ?? MhCatalog::OTHER_UNIT_OF_MEASURE);

        return $itemType === MhCatalog::SERVICE_ITEM_TYPE
            && $unit !== MhCatalog::OTHER_UNIT_OF_MEASURE;
    }

    private function documentType(): IssuableDteType
    {
        $type = $this->input('tipoDte');

        return (is_string($type) ? IssuableDteType::tryFrom($type) : null) ?? IssuableDteType::CONSUMER_INVOICE;
    }

    /** @return array<string, mixed> */
    private function receiverRules(): array
    {
        return match ($this->documentType()) {
            IssuableDteType::CONSUMER_INVOICE => $this->consumerReceiverRules(),
            IssuableDteType::TAX_CREDIT => $this->taxCreditReceiverRules(),
            IssuableDteType::EXCLUDED_SUBJECT => $this->excludedSubjectReceiverRules(),
        };
    }

    /** @return array<string, mixed> */
    private function consumerReceiverRules(): array
    {
        return [
            'receptor' => ['sometimes', 'nullable', 'array'],
            'receptor.nit' => ['prohibited'],
            'receptor.nombreComercial' => ['prohibited'],
            'receptor.tipoDocumento' => ['bail', 'nullable', 'required_with:receptor.numDocumento', 'string', Rule::exists(DocumentType::class, 'code')],
            'receptor.numDocumento' => ['nullable', 'required_with:receptor.tipoDocumento', 'string', 'max:20', ...$this->documentNumberFormat()],
            'receptor.nrc' => ['sometimes', 'nullable', 'string', 'min:2', 'max:8', 'regex:'.MhCatalog::NRC_PATTERN],
            'receptor.nombre' => ['sometimes', 'nullable', 'string', 'max:250'],
            'receptor.codActividad' => ['bail', 'sometimes', 'nullable', 'string', Rule::exists(EconomicActivity::class, 'code')],
            'receptor.descActividad' => ['sometimes', 'nullable', 'string', 'min:5', 'max:150'],
            'receptor.direccion' => ['sometimes', 'nullable', 'array'],
            ...$this->receiverAddressRules(),
            ...$this->receiverContactRules(6),
        ];
    }

    /**
     * Normativa 2.0, Anexo IV #57: only FE, CRE, FEXE, FSEE and CDE may leave the receiver email empty.
     *
     * @return array<string, mixed>
     */
    private function taxCreditReceiverRules(): array
    {
        return [
            'receptor' => ['required', 'array'],
            'receptor.tipoDocumento' => ['prohibited'],
            'receptor.numDocumento' => ['prohibited'],
            'receptor.nit' => ['required', 'string', 'regex:'.MhCatalog::NIT_PATTERN],
            'receptor.nrc' => ['required', 'string', 'min:2', 'max:8', 'regex:'.MhCatalog::NRC_PATTERN],
            'receptor.nombre' => ['required', 'string', 'max:250'],
            'receptor.codActividad' => ['bail', 'required', 'string', Rule::exists(EconomicActivity::class, 'code')],
            'receptor.descActividad' => ['required', 'string', 'min:5', 'max:150'],
            'receptor.nombreComercial' => ['sometimes', 'nullable', 'string', 'max:150'],
            'receptor.direccion' => ['required', 'array'],
            ...$this->receiverAddressRules(),
            ...$this->receiverContactRules(1, isEmailRequired: true),
        ];
    }

    /** @return array<string, mixed> */
    private function excludedSubjectReceiverRules(): array
    {
        return [
            'receptor' => ['required', 'array'],
            'receptor.nit' => ['prohibited'],
            'receptor.nrc' => ['prohibited'],
            'receptor.nombreComercial' => ['prohibited'],
            'receptor.tipoDocumento' => ['bail', 'required', 'string', Rule::exists(DocumentType::class, 'code')],
            'receptor.numDocumento' => ['required', 'string', 'max:20', ...$this->documentNumberFormat()],
            'receptor.nombre' => ['required', 'string', 'max:250'],
            'receptor.codActividad' => ['bail', 'sometimes', 'nullable', 'string', Rule::exists(EconomicActivity::class, 'code')],
            'receptor.descActividad' => ['sometimes', 'nullable', 'string', 'min:5', 'max:150'],
            'receptor.direccion' => ['required', 'array'],
            ...$this->receiverAddressRules(),
            ...$this->receiverContactRules(6),
        ];
    }

    /** @return array<int, string> */
    private function documentNumberFormat(): array
    {
        return match ($this->input('receptor.tipoDocumento')) {
            MhCatalog::DUI_DOCUMENT_TYPE => ['regex:'.MhCatalog::DUI_PATTERN],
            MhCatalog::NIT_DOCUMENT_TYPE => ['regex:'.MhCatalog::NIT_PATTERN],
            default => [],
        };
    }

    /** @return array<string, mixed> */
    private function receiverAddressRules(): array
    {
        $departament = $this->input('receptor.direccion.departamento');
        $municipality = $this->input('receptor.direccion.municipio');

        return [
            'receptor.direccion.departamento' => ['bail', 'required_with:receptor.direccion', 'string', 'size:2', Rule::exists(Departament::class, 'code')],
            'receptor.direccion.municipio' => [
                'bail',
                'required_with:receptor.direccion',
                'string',
                'size:2',
                Rule::exists(Municipalities::class, 'code')->where('departament_code', $departament),
            ],
            'receptor.direccion.distrito' => [
                'bail',
                'required_with:receptor.direccion',
                'string',
                'size:2',
                Rule::exists(District::class, 'code')->where(function (Builder $query) use ($departament, $municipality): void {
                    $query->whereIn('municipality_id', function (Builder $municipalities) use ($departament, $municipality): void {
                        $municipalities->select('id')
                            ->from('municipalities')
                            ->where('departament_code', $departament)
                            ->where('code', $municipality);
                    });
                }),
            ],
            'receptor.direccion.complemento' => ['required_with:receptor.direccion', 'string', 'max:200'],
        ];
    }

    /** @return array<string, mixed> */
    private function receiverContactRules(int $minimumEmailLength, bool $isEmailRequired = false): array
    {
        $emailPresence = $isEmailRequired ? ['required'] : ['sometimes', 'nullable'];

        return [
            'receptor.telefono' => ['sometimes', 'nullable', 'string', 'min:8', 'max:30'],
            'receptor.correo' => [...$emailPresence, 'email:filter', "min:{$minimumEmailLength}", 'max:100'],
        ];
    }

    /** @return array<string, mixed> */
    private function summaryRules(): array
    {
        $type = $this->documentType();
        $isExcludedSubject = $type === IssuableDteType::EXCLUDED_SUBJECT;
        $isExcludedSubjectOnCredit = $isExcludedSubject
            && (int) $this->input('resumen.condicionOperacion', MhCatalog::CASH_CONDITION) === MhCatalog::CREDIT_CONDITION;
        $paymentReferenceRules = ['sometimes', 'nullable', 'string', 'max:50'];

        if ($isExcludedSubject) {
            $paymentReferenceRules[] = $isExcludedSubjectOnCredit
                ? 'prohibited'
                : 'prohibited_if:resumen.pagos.*.codigo,'.MhCatalog::CASH_PAYMENT_METHOD;
        }

        return [
            'resumen' => ['sometimes', 'array'],
            ...($isExcludedSubject ? ['resumen.descu' => ['sometimes', ...$this->amountRules(2)]] : []),
            'resumen.ivaRete' => $isExcludedSubject ? ['prohibited'] : ['sometimes', ...$this->amountRules(2)],
            'resumen.ivaPerci' => $type === IssuableDteType::TAX_CREDIT ? ['sometimes', ...$this->amountRules(2)] : ['prohibited'],
            'resumen.reteRenta' => $isExcludedSubject ? ['sometimes', ...$this->amountRules(2)] : ['prohibited'],
            'resumen.condicionOperacion' => ['bail', 'sometimes', 'integer', Rule::in(MhCatalog::OPERATION_CONDITIONS)],
            'resumen.pagos' => ['bail', 'required_if:resumen.condicionOperacion,2,3', 'array', 'min:1'],
            'resumen.pagos.*.codigo' => $isExcludedSubjectOnCredit ? ['bail', 'prohibited', 'nullable', 'string'] : ['bail', 'required', 'string', Rule::exists(PaymentMethod::class, 'code')],
            'resumen.pagos.*.montoPago' => ['required', ...$this->amountRules(2), ...($isExcludedSubjectOnCredit ? ['max:0'] : [])],
            'resumen.pagos.*.referencia' => $paymentReferenceRules,
            'resumen.pagos.*.plazo' => ['bail', 'required_if:resumen.condicionOperacion,2', 'nullable', 'string', Rule::in(MhCatalog::PAYMENT_TERMS)],
            'resumen.pagos.*.periodo' => ['bail', 'required_if:resumen.condicionOperacion,2', 'nullable', 'numeric', 'gt:0'],
            'resumen.observaciones' => ['sometimes', 'nullable', 'string', 'max:3000'],
        ];
    }

    /** @return array<int, string> */
    private function amountRules(int $decimals): array
    {
        return ['bail', 'numeric', 'gte:0', 'lt:'.self::MAX_AMOUNT, "decimal:0,{$decimals}"];
    }
}
