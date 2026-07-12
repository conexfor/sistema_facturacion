import {
    InputHTMLAttributes,
    ReactNode,
    SelectHTMLAttributes,
    TextareaHTMLAttributes,
} from 'react';

const inputBase =
    'w-full rounded-lg border-slate-300 bg-white text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:ring-brand-500 disabled:bg-slate-50 disabled:text-slate-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';

function Wrapper({
    label,
    error,
    required,
    hint,
    children,
}: {
    label?: string;
    error?: string;
    required?: boolean;
    hint?: string;
    children: ReactNode;
}) {
    return (
        <div>
            {label && (
                <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">
                    {label}
                    {required && <span className="ml-0.5 text-rose-500">*</span>}
                </label>
            )}
            {children}
            {hint && !error && (
                <p className="mt-1 text-xs text-slate-400">{hint}</p>
            )}
            {error && <p className="mt-1 text-xs text-rose-600">{error}</p>}
        </div>
    );
}

export function TextField({
    label,
    error,
    required,
    hint,
    className = '',
    ...props
}: InputHTMLAttributes<HTMLInputElement> & {
    label?: string;
    error?: string;
    required?: boolean;
    hint?: string;
}) {
    return (
        <Wrapper label={label} error={error} required={required} hint={hint}>
            <input
                className={`${inputBase} ${error ? 'border-rose-400' : ''} ${className}`}
                {...props}
            />
        </Wrapper>
    );
}

export interface SelectOption {
    value: string | number;
    label: string;
}

export function SelectField({
    label,
    error,
    required,
    hint,
    options,
    placeholder,
    className = '',
    ...props
}: SelectHTMLAttributes<HTMLSelectElement> & {
    label?: string;
    error?: string;
    required?: boolean;
    hint?: string;
    options: SelectOption[];
    placeholder?: string;
}) {
    return (
        <Wrapper label={label} error={error} required={required} hint={hint}>
            <select
                className={`${inputBase} ${error ? 'border-rose-400' : ''} ${className}`}
                {...props}
            >
                {placeholder && <option value="">{placeholder}</option>}
                {options.map((o) => (
                    <option key={o.value} value={o.value}>
                        {o.label}
                    </option>
                ))}
            </select>
        </Wrapper>
    );
}

export function TextareaField({
    label,
    error,
    required,
    hint,
    className = '',
    ...props
}: TextareaHTMLAttributes<HTMLTextAreaElement> & {
    label?: string;
    error?: string;
    required?: boolean;
    hint?: string;
}) {
    return (
        <Wrapper label={label} error={error} required={required} hint={hint}>
            <textarea
                className={`${inputBase} ${error ? 'border-rose-400' : ''} ${className}`}
                {...props}
            />
        </Wrapper>
    );
}

export function Switch({
    label,
    checked,
    onChange,
    description,
}: {
    label: string;
    checked: boolean;
    onChange: (v: boolean) => void;
    description?: string;
}) {
    return (
        <label className="flex cursor-pointer items-center justify-between gap-3 rounded-lg border border-slate-200 px-3 py-2.5 dark:border-slate-700">
            <span>
                <span className="block text-sm font-medium text-slate-700 dark:text-slate-200">
                    {label}
                </span>
                {description && (
                    <span className="block text-xs text-slate-400">
                        {description}
                    </span>
                )}
            </span>
            <span className="relative inline-flex">
                <input
                    type="checkbox"
                    className="peer sr-only"
                    checked={checked}
                    onChange={(e) => onChange(e.target.checked)}
                />
                <span className="h-6 w-11 rounded-full bg-slate-300 transition peer-checked:bg-brand-600 dark:bg-slate-600" />
                <span className="absolute left-0.5 top-0.5 size-5 rounded-full bg-white transition peer-checked:translate-x-5" />
            </span>
        </label>
    );
}
