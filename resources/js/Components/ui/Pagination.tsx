import { PaginationLink } from '@/types/models';
import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

const MAX_PAGINAS = 7;

export default function Pagination({
    links,
    from,
    to,
    total,
}: {
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
}) {
    if (total === 0) return null;

    // Páginas numéricas (descartando "Anterior", "Siguiente" y los "...").
    const numeric = links
        .filter((l) => /^\d+$/.test(l.label))
        .map((l) => ({ page: Number(l.label), active: l.active }));
    const current = numeric.find((n) => n.active)?.page ?? 1;
    const last = numeric.length ? Math.max(...numeric.map((n) => n.page)) : 1;

    // Anterior / Siguiente vienen de Laravel (url null en los extremos).
    const prevUrl = links[0]?.url ?? null;
    const nextUrl = links[links.length - 1]?.url ?? null;

    // Plantilla para construir la URL de cualquier página conservando filtros.
    const sample = links.find((l) => l.url)?.url ?? null;
    const urlFor = (page: number): string | null => {
        if (!sample) return null;
        const u = new URL(sample, window.location.origin);
        u.searchParams.set('page', String(page));
        return u.pathname + u.search;
    };

    // Ventana deslizante de máximo 7 números centrada en la página actual.
    let start = Math.max(1, current - Math.floor(MAX_PAGINAS / 2));
    const end = Math.min(last, start + MAX_PAGINAS - 1);
    start = Math.max(1, end - MAX_PAGINAS + 1);
    const pages: number[] = [];
    for (let p = start; p <= end; p++) pages.push(p);

    const btn = 'inline-flex min-w-9 items-center justify-center gap-1 rounded-lg px-3 py-1.5 text-sm font-medium transition';
    const inactive = 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800';
    const disabled = 'cursor-default text-slate-300 dark:text-slate-600';

    return (
        <div className="flex flex-col items-center justify-between gap-3 border-t border-slate-100 px-1 pt-4 sm:flex-row dark:border-slate-800">
            <p className="text-xs text-slate-500 dark:text-slate-400">
                Mostrando <span className="font-semibold">{from ?? 0}</span>–
                <span className="font-semibold">{to ?? 0}</span> de{' '}
                <span className="font-semibold">{total}</span> registros
            </p>

            {last > 1 && (
                <div className="flex flex-wrap items-center gap-1">
                    {/* Anterior */}
                    {prevUrl ? (
                        <Link href={prevUrl} preserveScroll preserveState className={`${btn} ${inactive}`}>
                            <ChevronLeft className="size-4" /> Anterior
                        </Link>
                    ) : (
                        <span className={`${btn} ${disabled}`}>
                            <ChevronLeft className="size-4" /> Anterior
                        </span>
                    )}

                    {/* Números (máx. 7) */}
                    {pages.map((p) => {
                        const url = urlFor(p);
                        if (p === current || !url) {
                            return (
                                <span
                                    key={p}
                                    className={`${btn} ${p === current ? 'bg-brand-600 text-white' : disabled}`}
                                >
                                    {p}
                                </span>
                            );
                        }
                        return (
                            <Link
                                key={p}
                                href={url}
                                preserveScroll
                                preserveState
                                className={`${btn} ${inactive}`}
                            >
                                {p}
                            </Link>
                        );
                    })}

                    {/* Siguiente */}
                    {nextUrl ? (
                        <Link href={nextUrl} preserveScroll preserveState className={`${btn} ${inactive}`}>
                            Siguiente <ChevronRight className="size-4" />
                        </Link>
                    ) : (
                        <span className={`${btn} ${disabled}`}>
                            Siguiente <ChevronRight className="size-4" />
                        </span>
                    )}
                </div>
            )}
        </div>
    );
}
