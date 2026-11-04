import { yup } from 'ornito';
import { computed } from 'vue';
import type { ComputedRef, Ref } from 'vue';

export const createDteFormRules = (
    requiresReceiverIdentity: ComputedRef<boolean>,
    receptorDocumento: Ref<string>,
) => ({
    tipoDte: yup.string().required('Selecciona el tipo de comprobante.'),
    receiverName: computed(() => {
        const rules = yup
            .string()
            .trim()
            .max(250, 'El nombre no puede exceder 250 caracteres.');

        return requiresReceiverIdentity.value
            ? rules.required('El nombre del receptor es obligatorio.')
            : rules;
    }),
    receiverDocumentType: computed(() =>
        requiresReceiverIdentity.value || receptorDocumento.value.trim()
            ? yup.string().required('Selecciona el tipo de documento.')
            : yup.string().notRequired(),
    ),
    receiverDocument: computed(() => {
        const rules = yup
            .string()
            .trim()
            .max(20, 'El documento no puede exceder 20 caracteres.');

        return requiresReceiverIdentity.value
            ? rules.required('El número de documento es obligatorio.')
            : rules;
    }),
    receiverEmail: yup
        .string()
        .transform((value) =>
            typeof value === 'string' ? value.trim() || undefined : value,
        )
        .email('Ingresa un correo electrónico válido.')
        .max(100, 'El correo no puede exceder 100 caracteres.'),
    description: yup
        .string()
        .trim()
        .required('La descripción es obligatoria.')
        .max(1500, 'La descripción no puede exceder 1500 caracteres.'),
    quantity: yup
        .number()
        .typeError('La cantidad debe ser un número.')
        .required('La cantidad es obligatoria.')
        .moreThan(0, 'La cantidad debe ser mayor que cero.')
        .max(100_000_000_000, 'La cantidad excede el máximo permitido.'),
    unitPrice: yup
        .number()
        .typeError('El precio unitario debe ser un número.')
        .required('El precio unitario es obligatorio.')
        .min(1, 'El precio unitario debe ser al menos $0.01.')
        .max(
            10_000_000_000_000,
            'El precio unitario excede el máximo permitido.',
        ),
});
