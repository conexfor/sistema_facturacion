import { ButtonHTMLAttributes } from 'react';

type Variant = 'primary' | 'secondary' | 'danger' | 'ghost';
type Size = 'sm' | 'md';

const variants: Record<Variant, string> = {
    primary:
        'ff-btn-primary focus-visible:ring-brand-400',
    secondary:
        'bg-white text-slate-700 border border-slate-300 hover:bg-slate-50 focus-visible:ring-brand-500 dark:bg-white/[0.04] dark:text-ff-text dark:border-white/[0.08] dark:hover:bg-white/[0.08]',
    danger: 'bg-rose-600 text-white hover:bg-rose-700 focus-visible:ring-rose-500',
    ghost: 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-white/5',
};

const sizes: Record<Size, string> = {
    sm: 'px-3 py-1.5 text-xs gap-1.5',
    md: 'px-4 py-2 text-sm gap-2',
};

export function buttonClasses(variant: Variant = 'primary', size: Size = 'md') {
    return `inline-flex items-center justify-center rounded-lg font-display font-semibold transition duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-offset-white disabled:opacity-50 disabled:pointer-events-none dark:focus-visible:ring-offset-slate-900 ${variants[variant]} ${sizes[size]}`;
}

export default function Button({
    variant = 'primary',
    size = 'md',
    className = '',
    ...props
}: ButtonHTMLAttributes<HTMLButtonElement> & {
    variant?: Variant;
    size?: Size;
}) {
    return (
        <button className={`${buttonClasses(variant, size)} ${className}`} {...props} />
    );
}
