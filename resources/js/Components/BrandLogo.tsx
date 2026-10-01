import { FileText } from 'lucide-react';

/**
 * Marca FactuFlow: ícono de documento con gradiente cyan + wordmark en Outfit
 * con la segunda mitad resaltada.
 */
export default function BrandLogo({
    size = 'md',
    showWordmark = true,
    subtitle,
}: {
    size?: 'sm' | 'md' | 'lg';
    showWordmark?: boolean;
    subtitle?: string;
}) {
    const box = {
        sm: 'size-9 rounded-lg',
        md: 'size-10 rounded-xl',
        lg: 'size-12 rounded-xl',
    }[size];
    const icon = { sm: 'size-[18px]', md: 'size-5', lg: 'size-6' }[size];
    const text = { sm: 'text-lg', md: 'text-xl', lg: 'text-2xl' }[size];

    return (
        <div className="flex min-w-0 items-center gap-3">
            <span
                className={`flex shrink-0 items-center justify-center bg-gradient-to-br from-[#00d4aa] to-[#00a383] text-ff-base shadow-glow-cyan ${box}`}
            >
                <FileText className={icon} strokeWidth={2.25} />
            </span>
            {showWordmark && (
                <span className="min-w-0">
                    <span
                        className={`block truncate font-display font-bold leading-tight tracking-tight text-slate-800 dark:text-ff-text ${text}`}
                    >
                        Factu<span className="text-brand-500 dark:text-ff-cyan">Flow</span>
                    </span>
                    {subtitle && (
                        <span className="block truncate text-[11px] font-medium uppercase tracking-[0.14em] text-slate-400 dark:text-ff-muted">
                            {subtitle}
                        </span>
                    )}
                </span>
            )}
        </div>
    );
}
